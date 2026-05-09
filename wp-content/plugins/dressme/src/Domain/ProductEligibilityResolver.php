<?php

namespace Genesii\DressMe\Domain;

use Genesii\DressMe\Support\Options;
use Genesii\DressMe\Support\SettingsRepository;

final class ProductEligibilityResolver
{
    public function __construct(
        private readonly SettingsRepository $settingsRepository = new SettingsRepository(),
    ) {
    }

    public function isEligible(int $productId): bool
    {
        if (!$this->settingsRepository->isEnabled()) {
            return false;
        }

        $productMode = $this->settingsRepository->getProductOverride($productId);

        if (Options::PRODUCT_MODE_FORCE_DISABLE === $productMode) {
            return false;
        }

        if (Options::PRODUCT_MODE_FORCE_ENABLE === $productMode) {
            return true;
        }

        return $this->matchesCategoryRules($productId);
    }

    private function matchesCategoryRules(int $productId): bool
    {
        $mode = $this->settingsRepository->getVisibilityMode();
        $productCategoryIds = wc_get_product_term_ids($productId, 'product_cat');

        if (Options::VISIBILITY_ALL === $mode) {
            return true;
        }

        if (Options::VISIBILITY_INCLUDE === $mode) {
            $allowed = $this->settingsRepository->getAllowedCategoryIds();

            if ([] === $allowed) {
                return false;
            }

            return [] !== array_intersect($allowed, $productCategoryIds);
        }

        if (Options::VISIBILITY_EXCLUDE === $mode) {
            $excluded = $this->settingsRepository->getExcludedCategoryIds();

            if ([] === $excluded) {
                return true;
            }

            return [] === array_intersect($excluded, $productCategoryIds);
        }

        return true;
    }
}
