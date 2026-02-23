<?php

namespace App\UI\Controller\Admin;

use App\Domain\User\Entity\LoginAttempt;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class LoginAttemptCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return LoginAttempt::class;
    }
}
