<?php

namespace App\Controller;

use App\Entity\Contrats;
use App\Entity\Departement;
use App\Entity\Employe;
use App\Entity\EtatsRequetes;
use App\Entity\Groupes;
use App\Entity\Localisations;
use App\Entity\Requetes;
use App\Entity\Telephones;
use App\Entity\User;
use App\Form\ContactSuppportType;
use App\Form\EmployeInformationsType;
use App\Form\LocalisationType;
use App\Form\RequeteType;
use App\Form\ResponsableType;
use App\Form\TelephonesType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

class RhController extends AbstractController
{
    #[Route('/rh/listeDemandesComptes', name: 'listeDemandesComptes')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        //On récupère les demandes de comptes ou le statut est 'Demandé'
        $requetesRepo = $entityManager->getRepository(Requetes::class);

        $demandesComptes = $requetesRepo->findByEtatRequete('Demandé');

        return $this->render('rh/demandesCompte.html.twig', [
            'demandes' => $demandesComptes,
        ]);
    }

    #[Route('/rh/validerDemandeCompte/{id}', name: 'validerDemandeCompte')]
    public function validerDemandeCompte($id, EntityManagerInterface $entityManager, Request $request, MailerInterface $mailer): Response
    {
        //On récupère la demande de compte
        $requetesRepo = $entityManager->getRepository(Requetes::class);

        $demandeCompte = $requetesRepo->find($id);

        //On récupere l'objet EtatsRequetes avec l'état 'Validé par admin'
        $etatRequeteRepo = $entityManager->getRepository(EtatsRequetes::class);

        $etatRequete = $etatRequeteRepo->findOneBy(['etat' => 'Validé par RH']);

        //On change le statut de la demande de compte
        $demandeCompte->setEtatRequete($etatRequete);

        $entityManager->persist($demandeCompte);
        $entityManager->flush();

        //On envoie un mail aux admins pour les informer de la demande de compte
        $userRepo = $entityManager->getRepository(User::class);
        $admins = $userRepo->findByRole('ROLE_ADMIN');

        //On envoie un mail à chaque admin
        foreach ($admins as $admin) {

            $message = "Bonjour, \n\n";
            $message .= "L'utilisateur " . $demandeCompte->getPrenom() . " " . $demandeCompte->getNom() . " a été validé par le service RH. \n";
            $message .= "Vous pouvez maintenant créer son compte. \n\n";
            $message .= "Cordialement, \n";

            $email = (new Email())
                ->from('you@example.com')
                ->to($admin->getEmail())
                ->subject('Validation de demande de compte numéro : ' . $demandeCompte->getDateRequete()->format('YmdHis'))
                ->text($message);

            try {
                //$mailer->send($email);
            } catch (TransportExceptionInterface $e) {
                $session = $request->getSession();
                $session->getFlashBag()->add('message', 'Erreur lors de l\'envoi du mail.');
                $session->set('statut', 'danger');
                return $this->redirect($this->generateUrl('validerDemandeCompte'));
            }
        }


        //On crée un message flash pour informer l'utilisateur que la demande a bien été validée
        $session = $request->getSession();
        $session->getFlashBag()->add('message', "L'utilisateur a bien été validé.");
        $session->set('statut', 'success');

        return $this->redirectToRoute('listeDemandesComptes');
    }

    #[Route('/rh/refuserDemandeCompte/{id}', name: 'refuserDemandeCompte')]
    public function refuserDemandeCompte($id, EntityManagerInterface $entityManager, Request $request): Response
    {
        //On récupère la demande de compte
        $requetesRepo = $entityManager->getRepository(Requetes::class);

        $demandeCompte = $requetesRepo->find($id);

        //On récupere l'objet EtatsRequetes avec l'état 'Refusé par admin'
        $etatRequeteRepo = $entityManager->getRepository(EtatsRequetes::class);

        $etatRequete = $etatRequeteRepo->findOneBy(['etat' => 'Refusé']);

        //On change le statut de la demande de compte
        $demandeCompte->setEtatRequete($etatRequete);

        $entityManager->persist($demandeCompte);
        $entityManager->flush();

        //On crée un message flash pour informer l'utilisateur que la demande a bien été refusée
        $session = $request->getSession();
        $session->getFlashBag()->add('message', "L'utilisateur a bien été refusé.");
        $session->set('statut', 'success');

        return $this->redirectToRoute('listeDemandesComptes');
    }

    #[Route('/rh/modifierDemandeCompte/{id}', name: 'modifierDemandeCompte')]
    public function modifierDemandeCompte($id, EntityManagerInterface $entityManager, Request $request): Response
    {
        //On récupère la demande de compte
        $requetesRepo = $entityManager->getRepository(Requetes::class);

        $demandeCompte = $requetesRepo->find($id);

        //On crée un formulaire pour modifier la demande de compte
        $form = $this->createForm(RequeteType::class, $demandeCompte);

        $form->add('valider', SubmitType::class, ['label' => 'Valider']);
        $form->add('annuler', SubmitType::class, ['label' => 'Annuler', 'attr' => ['class' => 'btn-secondary']]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $form->get('valider')->isClicked()) {
            $entityManager->persist($demandeCompte);
            $entityManager->flush();

            //On crée un message flash pour informer l'utilisateur que la demande a bien été modifiée
            $session = $request->getSession();
            $session->getFlashBag()->add('message', "La demande a bien été modifiée.");
            $session->set('statut', 'success');

            return $this->redirectToRoute('listeDemandesComptes');
        }
        else if ($form->get('annuler')->isClicked()) {
            return $this->redirectToRoute('listeDemandesComptes');
        }

        return $this->render('rh/modifierDemandeCompte.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/rh/listeDepartements/{index}', name: 'listeDepartements', requirements: ['index' => '\d*'], defaults: ['index' => null])]
    public function listeGroupesParDepartements(EntityManagerInterface $entityManager, Request $request, $index): Response
    {
        //On récupère les departements
        $departementRepo = $entityManager->getRepository(Departement::class);

        $departements = $departementRepo->findAll();

        $formsResponsableDep = [];
        foreach ($departements as $i => $departement) {
            //On crée un formulaire pour modifier le responsable du département
            $form = $this->createFormBuilder($departement)
                ->add('responsable', EntityType::class, [
                    'class' => Employe::class,
                    'choice_label' => function ($employe) {
                        return $employe->getPrenom() . ' ' . $employe->getNom();
                    },
                    'label' => 'Responsable :',
                ])
                ->getForm();

            $form->add('modifier', SubmitType::class, [
                'label' => 'Modifier',
                'attr' => ['data-index' => $index],
            ]);

            if ($index == $i) {
                $form->handleRequest($request);
            }


            if ($form->isSubmitted() && $form->isValid() && $index == $i && $form->get('modifier')->isClicked()) {

                $entityManager->persist($departement);
                $entityManager->flush();

                //On crée un message flash pour informer l'utilisateur que le responsable a bien été modifié
                $session = $request->getSession();
                $session->getFlashBag()->add('message', "Le responsable du département a bien été modifié.");
                $session->set('statut', 'success');

                return $this->redirectToRoute('listeDepartements');
            }

            $formsResponsableDep[] = $form->createView();
        }

        return $this->render('rh/listeDepartements.html.twig', [
            'departements' => $departements,
            'formsResponsableDep' => $formsResponsableDep,
        ]);
    }

    #[Route('/rh/listeGroupe/{id}', name: 'listeGroupe')]
    public function listeGroupe($id, EntityManagerInterface $entityManager, Request $request): Response
    {

        //On récupère le departement
        $groupesRepo = $entityManager->getRepository(Groupes::class);

        $groupe = $groupesRepo->find($id);

        //On crée un formulaire pour modifier le responsable du groupe
        $form = $this->createForm(ResponsableType::class, $groupe);

        $form->add('submit', SubmitType::class, [
            'label' => 'Modifier',
        ]);


        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($groupe);
            $entityManager->flush();

            //On crée un message flash pour informer l'utilisateur que le responsable a bien été modifié
            $session = $request->getSession();
            $session->getFlashBag()->add('message', "Le responsable a bien été modifié.");
            $session->set('statut', 'success');

            return $this->redirectToRoute('listeGroupe', ['id' => $id]);
        }

        //Formulaire ajout d'un employé dans le groupe
        //On récupère les employés qui ne sont pas dans le groupe
        $employeRepo = $entityManager->getRepository(Employe::class);

        $employes = $employeRepo->findAll();

        $employesGroupe = $groupe->getEmployesGrpPrincipaux()->toArray();

        $employesNonGroupe = array_udiff($employes, $employesGroupe,
            function ($obj_a, $obj_b) {
                return $obj_a->getId() - $obj_b->getId();
            }
        );

        //On met dans le formulaire les nom des employés
        $formAjoutEmploye = $this->createFormBuilder()
            ->add('groupesSecondaires', EntityType::class, [
                'choices' => $employesNonGroupe,
                'class' => Employe::class,
                'choice_label' => function ($employe) {
                    return $employe->getPrenom() . ' ' . $employe->getNom();
                },
                'label' => 'Ajouter un employé :',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Ajouter',
            ])
            ->getForm();

        $formAjoutEmploye->handleRequest($request);

        if ($formAjoutEmploye->isSubmitted() && $formAjoutEmploye->isValid()) {
            $data = $formAjoutEmploye->getData();

            $employe = $data['groupesSecondaires'];

            $employe->addGroupesSecondaire($groupe);

            $entityManager->persist($employe);
            $entityManager->flush();

            //On crée un message flash pour informer l'utilisateur que l'employé a bien été ajouté
            $session = $request->getSession();
            $session->getFlashBag()->add('message', "L'employé a bien été ajouté.");
            $session->set('statut', 'success');

            return $this->redirectToRoute('listeGroupe', ['id' => $id]);
        }


        return $this->render('rh/listeGroupe.html.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
            'formAjoutEmploye' => $formAjoutEmploye->createView(),
        ]);
    }

    #[Route('/rh/infoEmploye/{idEmploye}/{idGroupe}', name: 'infoEmploye')]
    public function infoEmploye($idEmploye, $idGroupe, EntityManagerInterface $entityManager, Request $request): Response
    {
        //On récupère l'employé
        $employeRepo = $entityManager->getRepository(Employe::class);

        $employe = $employeRepo->find($idEmploye);


        //On récupère le User connecté
        $user = $employe->getUser();

        //On crée un formulaire pour modifier les informations de l'employé
        $form = $this->createForm(EmployeInformationsType::class, $employe);

        $form->add('submit', SubmitType::class, [
            'label' => 'Modifier',
        ]);
        $form->add('annuler', SubmitType::class, [
            'label' => 'Annuler',
            'attr' => ['class' => 'btn-secondary'],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $form->get('submit')->isClicked()) {
            $entityManager->persist($employe);
            $entityManager->flush();

            //On crée un message flash pour informer l'utilisateur que les informations ont bien été modifiées
            $session = $request->getSession();
            $session->getFlashBag()->add('message', "Les informations ont bien été modifiées.");
            $session->set('statut', 'success');

            return $this->redirectToRoute('infoEmploye', ['idEmploye' => $idEmploye]);
        }
        else if ($form->get('annuler')->isClicked()) {
            return $this->redirectToRoute('listeGroupe', ['id' => $idGroupe]);
        }

        return $this->render('rh/infoEmploye.html.twig', [
            'employe' => $employe,
            'form' => $form->createView(),
        ]);
    }
}
