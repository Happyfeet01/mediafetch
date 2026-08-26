<?php

declare(strict_types=1);

namespace OCA\NCDownloader\Settings;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class PersonalSection implements IIconSection
{
    private $l;
    private $urlGenerator;

    public function __construct(IL10N $l, IURLGenerator $urlGenerator)
    {
        $this->l = $l;
        $this->urlGenerator = $urlGenerator;
    }

    public function getIcon(): string
    {
        return $this->urlGenerator->imagePath('mediafetch', 'mediafetch.svg');
    }

    public function getID(): string
    {
        return 'mediafetch';
    }

    public function getName(): string
    {
        return $this->l->t('MediaFetch');
    }

    public function getPriority(): int
    {
        return 100;
    }
}
