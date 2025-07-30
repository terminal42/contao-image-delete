<?php

declare(strict_types=1);

namespace Terminal42\ImageDeleteBundle\Controller;

use Contao\CoreBundle\Controller\AbstractBackendController;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ImageDeleteController extends AbstractBackendController
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly RouterInterface $router,
        private readonly Filesystem $filesystem,
        private readonly ContaoCsrfTokenManager $csrfTokenManager,
        private readonly string $projectDir,
        private readonly string $imageTargetDir,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $this->framework->initialize();
        $path = urldecode($request->query->get('path'));

        if (!$this->filesystem->exists($this->projectDir.'/'.$path) || !is_file($this->projectDir.'/'.$path)) {
            throw new NotFoundHttpException('File "'.$path.'" was not found.');
        }

        if (!$this->authorizationChecker->isGranted('contao_user.filemounts', \dirname($path)) || !$this->authorizationChecker->isGranted('contao_user.fop', 'f3')) {
            throw new AccessDeniedException('No permissions to access "'.\dirname($path).'" or delete files.');
        }

        $assetsDir = ltrim(str_replace($this->projectDir, '', $this->imageTargetDir), '/');

        $finder = (new Finder())
            ->in($assetsDir)
            ->files()
            ->name(sprintf('%s-*', pathinfo($path, PATHINFO_FILENAME)))
        ;

        $assets = [];

        foreach ($finder as $file) {
            if ('json' === $file->getExtension()) {
                $assets[$file->getPathname()] = str_replace('/deferred', '', $file->getPath()).'/'.$file->getFilenameWithoutExtension();
            } else {
                $assets[$file->getPathname()] = $file->getPathname();
            }
        }

        if ('terminal42_image_delete' === $request->request->get('FORM_SUBMIT')) {
            $ids = $request->request->all('IDS');
            $imagesToDelete = array_intersect($ids, array_keys($assets));

            if (\in_array($path, $ids, true)) {
                $imagesToDelete[] = $path;
            }

            $imagesToDelete = array_map(fn ($file) => $this->projectDir.'/'.$file, $imagesToDelete);
            $this->filesystem->remove($imagesToDelete);

            return new RedirectResponse($this->router->generate('contao_backend', ['do' => 'files']));
        }

        return $this->render('@Terminal42ImageDelete/image-delete.html.twig', [
            'request_token' => $this->csrfTokenManager->getDefaultTokenValue(),
            'back' => $this->router->generate('contao_backend', ['do' => 'files']),
            'file' => $path,
            'assets' => $assets,
        ]);
    }
}
