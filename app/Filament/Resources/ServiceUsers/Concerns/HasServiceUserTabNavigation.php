<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceUsers\Concerns;

use App\Filament\Resources\ServiceUsers\Schemas\ServiceUserForm;

trait HasServiceUserTabNavigation
{
    public string $activeServiceUserTab = 'demographics-consent';

    public function previousTab(): void
    {
        $currentIndex = array_search($this->activeServiceUserTab, ServiceUserForm::TABS, true);

        if ($currentIndex !== false && $currentIndex > 0) {
            $this->activeServiceUserTab = ServiceUserForm::TABS[$currentIndex - 1];
        }
    }

    public function nextTab(): void
    {
        $currentIndex = array_search($this->activeServiceUserTab, ServiceUserForm::TABS, true);

        if ($currentIndex !== false && $currentIndex < count(ServiceUserForm::TABS) - 1) {
            $this->activeServiceUserTab = ServiceUserForm::TABS[$currentIndex + 1];
        }
    }
}
