<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Hidden;

class ClientDateTimeFormComponent extends Hidden
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrated();
        $this->default('');

        $this->extraAttributes([
            'x-data' => '',
            'x-init' => $this->getClientDateTimeScript(),
        ]);
    }

      protected function getClientDateTimeScript(): string
    {
        return '
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, "0");
            const day = String(now.getDate()).padStart(2, "0");
            const hours = String(now.getHours()).padStart(2, "0");
            const minutes = String(now.getMinutes()).padStart(2, "0");
            const seconds = String(now.getSeconds()).padStart(2, "0");

            const clientDateTime = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
            $el.value = clientDateTime;
            $el.dispatchEvent(new Event("input", { bubbles: true }));
        ';
    }

    // Optional: Add configuration methods
    public function withSeconds(bool $withSeconds = true): static
    {
        // You can extend this to make the component configurable
        return $this;
    }

    public function format12Hour(bool $format12Hour = true): static
    {
        // You can extend this to make the component configurable
        return $this;
    }
}
