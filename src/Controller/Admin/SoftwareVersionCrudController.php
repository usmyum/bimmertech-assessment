<?php

namespace App\Controller\Admin;

use App\Entity\SoftwareVersion;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

class SoftwareVersionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SoftwareVersion::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Software Version')
            ->setEntityLabelInPlural('Software Versions')
            ->setPageTitle('index', 'All Software Versions')
            ->setPageTitle('new', 'Add New Software Version')
            ->setPageTitle('edit', fn(SoftwareVersion $sv) => 'Edit: ' . $sv->getName() . ' — ' . $sv->getSystemVersion())
            ->setDefaultSort(['name' => 'ASC', 'systemVersionAlt' => 'ASC'])
            ->setSearchFields(['name', 'systemVersion', 'systemVersionAlt'])
            ->setHelp('index', 'Each row is one firmware version for one product variant. The "Latest" flag means the customer already has the newest version — no download link needed.')
            ->setHelp('new', 'Add a new firmware version. Make sure the System Version Alt matches exactly what appears on the customer\'s device (without the leading "v").')
            ->setHelp('edit', '⚠️ Changing version strings or download links takes effect immediately for all customers. Double-check before saving.');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield TextField::new('name', 'Product Name')
            ->setHelp('e.g. "MMI Prime CIC", "LCI MMI PRO EVO". Must start with "LCI " for LCI hardware variants.')
            ->setColumns(4);

        yield TextField::new('systemVersion', 'Full Version String')
            ->setHelp('With leading "v", e.g. v3.3.7.mmipri.c')
            ->setColumns(4);

        yield TextField::new('systemVersionAlt', 'Customer Version String')
            ->setHelp('WITHOUT leading "v" — this is exactly what the customer types in, e.g. 3.3.7.mmipri.c')
            ->setColumns(4);

        yield BooleanField::new('latest', 'Is Latest Version?')
            ->setHelp('Tick this if this version is already the latest. The customer will be told their system is up to date and no download link will be shown. Only ONE version per product should be marked latest.')
            ->renderAsSwitch(true);

        yield UrlField::new('link', 'General Download Link')
            ->setHelp('Google Drive folder link for full firmware package. Leave empty for LCI versions.')
            ->setRequired(false)
            ->hideOnIndex();

        yield UrlField::new('st', 'ST Hardware Download Link')
            ->setHelp('Download link for ST (standard) hardware. Leave empty if not applicable.')
            ->setRequired(false)
            ->hideOnIndex();

        yield UrlField::new('gd', 'GD Hardware Download Link')
            ->setHelp('Download link for GD hardware (NBT/EVO). Leave empty if not applicable or if this is the latest version.')
            ->setRequired(false)
            ->hideOnIndex();

        // Compact summary columns for the list view
        if ($pageName === Crud::PAGE_INDEX) {
            yield BooleanField::new('latest', 'Latest')->renderAsSwitch(false);
            yield TextField::new('st', 'ST Link')->formatValue(fn($v) => $v ? '✔' : '—');
            yield TextField::new('gd', 'GD Link')->formatValue(fn($v) => $v ? '✔' : '—');
        }
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name', 'Product Name'))
            ->add(BooleanFilter::new('latest', 'Latest Only'));
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::NEW, fn(Action $a) => $a->setLabel('Add New Version')->setIcon('fa fa-plus'))
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn(Action $a) => $a->setLabel('Delete'));
    }
}
