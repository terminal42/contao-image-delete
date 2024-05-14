<?php

declare(strict_types=1);

namespace Terminal42\ImageDeleteBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\File;
use Contao\Image;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

#[AsCallback(table: 'tl_files', target: 'list.operations.delete.button')]
class FileDeleteOperationListener
{
    private bool|null $canDeleteFiles = null;

    public function __construct(
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $projectDir,
    ) {
    }

    public function __invoke(array $row, string|null $href, string|null $label, string|null $title, string|null $icon, string|null $attributes): string
    {
        if (null === $this->canDeleteFiles) {
            $this->canDeleteFiles = $this->security->isGranted('contao_user.fop', 'f3');
        }

        $path = urldecode((string) $row['id']);

        if (!$this->canDeleteFiles || is_dir($this->projectDir.'/'.$path) || !(new File($path))->isImage) {
            return System::importStatic(\tl_files::class)->deleteFile($row, $href, $label, $title, $icon, $attributes);
        }

        return '<a href="'.$this->urlGenerator->generate('terminal42_image_delete', ['path' => $row['id']]).'" title="'.StringUtil::specialchars($title).'">'.Image::getHtml($icon, $label).'</a> ';
    }
}
