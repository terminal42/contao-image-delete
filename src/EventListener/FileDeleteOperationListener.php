<?php

declare(strict_types=1);

namespace Terminal42\ImageDeleteBundle\EventListener;

use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\File;
use Contao\System;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\ResetInterface;

#[AsCallback(table: 'tl_files', target: 'list.operations.delete.button')]
class FileDeleteOperationListener implements ResetInterface
{
    private bool|null $canDeleteFiles = null;

    public function __construct(
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
        #[Autowire(param: 'kernel.project_dir')] private readonly string $projectDir,
    ) {
    }

    public function __invoke(DataContainerOperation $operation): void
    {
        if (null === $this->canDeleteFiles) {
            $this->canDeleteFiles = $this->security->isGranted('contao_user.fop', 'f3');
        }

        $path = urldecode((string) $operation->getRecord()['id']);

        if (!$this->canDeleteFiles || is_dir($this->projectDir.'/'.$path) || !(new File($path))->isImage) {
            System::importStatic(\tl_files::class)->deleteFile($operation);

            return;
        }

        $operation->setUrl($this->urlGenerator->generate('terminal42_image_delete', ['path' => $operation->getRecord()['id']]));
        $operation['attributes']->unset('onclick');
    }

    public function reset(): void
    {
        $this->canDeleteFiles = null;
    }
}
