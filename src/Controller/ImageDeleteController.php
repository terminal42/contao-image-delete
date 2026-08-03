<?php

declare(strict_types=1);

namespace Terminal42\ImageDeleteBundle\Controller;

use Contao\CoreBundle\Controller\Backend\AbstractBackendController;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\FilesModel;
use Contao\System;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route('%contao.backend.route_prefix%/image-delete', 'terminal42_image_delete', defaults: ['_scope' => 'backend', '_token_check' => true])]
class ImageDeleteController extends AbstractBackendController
{
    public function __construct(
        private readonly Filesystem $filesystem,
        #[Autowire(param: 'kernel.project_dir')] private readonly string $projectDir,
        #[Autowire(param: 'contao.image.target_dir')] private readonly string $imageTargetDir,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $this->initializeContaoFramework();
        System::loadLanguageFile('default');

        $path = urldecode($request->query->get('path'));
        $filesModel = FilesModel::findByPath($path);

        if (!$filesModel || 'file' !== $filesModel->type || !$this->filesystem->exists($this->projectDir.'/'.$path)) {
            throw new NotFoundHttpException('File "'.$path.'" was not found.');
        }

        $this->denyAccessUnlessGranted(ContaoCorePermissions::DC_PREFIX.'tl_files', new DeleteAction('tl_files', $filesModel->row()));

        $assetsDir = ltrim(str_replace($this->projectDir, '', $this->imageTargetDir), '/');

        $finder = (new Finder())
            ->in($assetsDir)
            ->files()
            ->name(\sprintf('%s-*', pathinfo($path, PATHINFO_FILENAME)))
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
                $filesModel->delete();
            }

            $imagesToDelete = array_map(fn ($file) => $this->projectDir.'/'.$file, $imagesToDelete);
            $this->filesystem->remove($imagesToDelete);

            return new RedirectResponse($this->generateUrl('contao_backend', ['do' => 'files']));
        }

        return $this->render('@Contao/backend/image-delete.html.twig', [
            'back' => $this->generateUrl('contao_backend', ['do' => 'files']),
            'file' => $path,
            'assets' => $assets,
        ]);
    }
}
