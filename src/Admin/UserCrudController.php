<?php

declare(strict_types=1);

namespace App\Admin;

use App\Domain\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setPageTitle('index', 'Gestion des utilisateurs')
            ->setPageTitle('new', 'Créer un utilisateur')
            ->setPageTitle('edit', 'Modifier un utilisateur')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['email']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield EmailField::new('email', 'Email');

        if ($pageName === Crud::PAGE_NEW) {
            yield TextField::new('plainPassword', 'Mot de passe')
                ->setRequired(true);
        }

        yield ChoiceField::new('roles', 'Rôles')
            ->setChoices([
                'Utilisateur'    => 'ROLE_USER',
                'Administrateur' => 'ROLE_ADMIN',
            ])
            ->allowMultipleChoices()
            ->renderAsBadges([
                'ROLE_ADMIN' => 'danger',
                'ROLE_USER'  => 'success',
            ]);

        yield BooleanField::new('isLocked', 'Bloqué')
            ->renderAsSwitch(false)
            ->setDisabled();

        yield IntegerField::new('failedLoginCount', 'Tentatives')->hideOnForm();
        yield DateTimeField::new('lockedAt', 'Bloqué le')->hideOnForm()->setFormat('dd/MM/yyyy HH:mm');
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm()->setFormat('dd/MM/yyyy HH:mm');
    }

    public function configureActions(Actions $actions): Actions
    {
        $unlockAction = Action::new('unlockUser', 'Débloquer', 'fa fa-unlock')
            ->linkToCrudAction('unlockUser')
            ->displayIf(fn(User $user) => $user->isLocked())
            ->setCssClass('btn btn-warning btn-sm');

        return $actions
            ->add(Crud::PAGE_INDEX, $unlockAction)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('email')
            ->add('isLocked')
            ->add('roles');
    }

    public function unlockUser(AdminContext $context): RedirectResponse
    {
        /** @var User $user */
        $user = $context->getEntity()->getInstance();
        $user->unlock();

        $this->container->get('doctrine')->getManager()->flush();
        $this->addFlash('success', "Utilisateur {$user->getEmail()} débloqué.");

        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        return $this->redirect(
            $adminUrlGenerator
                ->setController(self::class)
                ->setAction(Crud::PAGE_INDEX)
                ->generateUrl()
        );
    }

    public function persistEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        if ($entityInstance instanceof User) {
            $plain = $_POST['User']['plainPassword'] ?? null;

            if (!empty($plain)) {
                $hashed = $this->passwordHasher->hashPassword($entityInstance, $plain);
                $entityInstance->setPassword($hashed);
            }
        }

        parent::persistEntity($entityManager, $entityInstance);
    }
}
