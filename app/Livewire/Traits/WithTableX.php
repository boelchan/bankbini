<?php

namespace App\Livewire\Traits;

use Livewire\WithPagination;

trait WithTableX
{
    use WithPagination;

    // public function queryStringWithTableX()
    // {
    //     $qs = [];
    //     foreach (get_object_vars($this) as $property => $value) {
    //         if (str_starts_with($property, 'search_')) {
    //             $qs[$property] = ['except' => ''];
    //         }
    //     }
    //     return $qs;
    // }

    public function mountWithTableX()
    {
        if (property_exists($this, 'sortFieldDefault')) {
            $this->sortField = $this->sortFieldDefault;
        }

        if (property_exists($this, 'sortDirectionDefault')) {
            $this->sortDirection = $this->sortDirectionDefault;
        }
    }

    public $perPage = 50;

    public bool $open = false;

    public $sortField = 'id';

    public $sortDirection = 'desc';

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage(); // supaya balik ke page 1 kalau perPage diubah
    }

    public function updated($property)
    {
        if (str_starts_with($property, 'search_')) {
            $this->resetPage();
        }
    }

    public function resetFilters()
    {
        foreach (get_object_vars($this) as $property => $value) {
            if (str_starts_with($property, 'search_')) {
                $this->$property = null;
            }
        }
    }

    public function paginationView()
    {
        return 'vendor.pagination.custom';
    }
}
