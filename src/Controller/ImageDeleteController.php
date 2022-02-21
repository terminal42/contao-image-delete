<?php

declare(strict_types=1);

namespace Terminal42\ImageDeleteBundle\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FilesModel;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Twig\Environment;

/**
 * @Route("/contao/image-delete/", name="terminal42_image_delete", defaults={"_scope" = "backend", "_token_check" = true})
 */
class ImageDeleteController
{
    private ContaoFramework $framework;
    private Security $security;
    private Environment $twig;
    private RouterInterface $router;
    private Filesystem $filesystem;
    private string $projectDir;
    private string $imageTargetDir;

    public function __construct(ContaoFramework $framework, Security $security, Environment $twig, RouterInterface $router, Filesystem $filesystem, string $projectDir, string $imageTargetDir)
    {
        $this->framework = $framework;
        $this->security = $security;
        $this->twig = $twig;
        $this->router = $router;
        $this->filesystem = $filesystem;
        $this->projectDir = $projectDir;
        $this->imageTargetDir = $imageTargetDir;
    }

    public function __invoke(Request $request): Response
    {
        $this->framework->initialize();

        $fileModel = FilesModel::findByPath(urldecode($request->query->get('path')));

        if (null === $fileModel) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted('contao_user.filemounts', \dirname($fileModel->path)) || !$this->security->isGranted('contao_user.fop', 'f3')) {
            throw new AccessDeniedException();
        }

        $assetsDir = ltrim(str_replace($this->projectDir, '', $this->imageTargetDir), '/');

        $finder = (new Finder())
            ->in($assetsDir)
            ->files()
            ->name(sprintf('%s-*', pathinfo($fileModel->path, PATHINFO_FILENAME)))
        ;

        $assets = [];

        foreach ($finder as $file) {
            if ('json' === $file->getExtension()) {
                $assets[] = str_replace('/deferred', '', $file->getPath()).'/'.$file->getFilenameWithoutExtension();
            } else {
                $assets[] = $file->getPathname();
            }
        }

        if ('terminal42_image_delete' === $request->request->get('FORM_SUBMIT')) {
            $ids = $request->request->get('IDS', []);
            $imagesToDelete = array_intersect($ids, $assets);

            if (\in_array($fileModel->path, $ids, true)) {
                $imagesToDelete[] = $fileModel->path;
            }

            $imagesToDelete = array_map(fn ($file) => $this->projectDir.'/'.$file, $imagesToDelete);
            $this->filesystem->remove($imagesToDelete);

            return new RedirectResponse($this->router->generate('contao_backend', ['do' => 'files']));
        }

        return new Response($this->twig->render(
            '@Terminal42ImageDelete/image-delete.html.twig',
            [
                'request_token' => REQUEST_TOKEN,
                'back' => $this->router->generate('contao_backend', ['do' => 'files']),
                'file' => $fileModel,
                'assets' => $assets,
            ]
        ));
    }
}
