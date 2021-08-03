<?php

namespace Terminal42\ImageDeleteBundle\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\File;
use Contao\Image;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

/**
 * @Callback(table="tl_files", target="list.operations.delete.button")
 */
class FileDeleteOperationListener
{
    private Security $security;
    private UrlGeneratorInterface $urlGenerator;
    private string $projectDir;

    private ?bool $canDeleteFiles = null;

    public function __construct(Security $security, UrlGeneratorInterface $urlGenerator, string $projectDir)
    {
        $this->security = $security;
        $this->urlGenerator = $urlGenerator;
        $this->projectDir = $projectDir;
    }

    public function __invoke($row, $href, $label, $title, $icon, $attributes): string
    {
        if (null === $this->canDeleteFiles) {
            $this->canDeleteFiles = $this->security->isGranted('contao_user.fop', 'f3');
        }

        $path = urldecode($row['id']);

        if (!$this->canDeleteFiles || is_dir($this->projectDir.'/'.$path) || !(new File($path))->isImage) {
            return System::importStatic(\tl_files::class)->deleteFile($row, $href, $label, $title, $icon, $attributes);
        }

        return '<a href="' . $this->urlGenerator->generate('terminal42_image_delete', ['path' => $row['id']]) . '" title="' . StringUtil::specialchars($title) . '">' . Image::getHtml($icon, $label) . '</a> ';
    }
}
