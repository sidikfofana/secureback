<?php

namespace App\Controller;

use App\Entity\Company;
use App\Helpers\Helpers;
use App\Entity\Courriers;
use App\Services\FileUploader;
use Symfony\Component\Mime\Email;
use App\Repository\CourriersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


class CourriersController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private Helpers $Helpers,
        private EntityManagerInterface $em,
        private MailerInterface $mailer,
        private FileUploader $fileUploader,
        private TokenStorageInterface $tokenStorage,
    ) {
    } 

    #[Route('/api/create-courriers', name: 'app_create_courriers', methods: ['POST'])]
    public function createCourrier(Request $request): JsonResponse
    {
        try {
            $data = $request->request->all();
            $file = $request->files->get('signature_file');

            if ($data === null) {
                throw new \InvalidArgumentException('Invalid JSON data');
            }

            // Champs requis
            $requiredFields = ['objet', 'nom_destinateur', 'prenoms_destinateur', 'adresse_destinateur', 'email_destinateur', 'contact_destinateur', 'lieu', 'date', 'description', 'civilite', 'destinataire', 'category'];
            $missingFields = $this->Helpers->validateRequiredFields($data, $requiredFields);

            if (!empty($missingFields)) {
                throw new \InvalidArgumentException('Missing required fields: ' . implode(', ', $missingFields));
            }

            // On tente de récupérer l'utilisateur s'il existe, mais ce n’est plus obligatoire
            $user = $this->tokenStorage->getToken()?->getUser();
            /*if (!$user || !is_object($user)) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Utilisateur non authentifié.'
                ], Response::HTTP_UNAUTHORIZED);
            }*/

            $uidn = sprintf('artci-%s-%s', date('Ymd'), strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)));

            $courrier = new Courriers();
            //$courrier->setUser($user);
            if ($user && is_object($user)) {
                $courrier->setUser($user);
            }

            // Récupération et association à une entreprise si fourni
            if (!empty($data["company_id"])) {
                $company = $this->entityManager->getRepository(Company::class)->find($data['company_id']);
                if ($company) {
                    $courrier->setCompany($company);
                }
            }

            // Hydratation de l'entité
            $courrier->setObjet($data['objet']);
            $courrier->setNomDestinateur($data['nom_destinateur']);
            $courrier->setPrenomsDestinateur($data['prenoms_destinateur']);
            $courrier->setAdresseDestinateur($data['adresse_destinateur']);
            $courrier->setEmailDestinateur($data['email_destinateur']);
            $courrier->setContactDestinateur($data['contact_destinateur']);
            $courrier->setLieu($data['lieu']);
            $courrier->setDate(new \DateTime($data['date']));
            $courrier->setDescription($data['description']);
            $courrier->setCivilite($data['civilite']);
            $courrier->setDestinataire($data['destinataire']);
            $courrier->setCreatedAt(new \DateTimeImmutable());
            $courrier->setUpdatedAt(new \DateTimeImmutable());
            $courrier->setStatus(false); // Par défaut, le statut est actif
            $courrier->setUidn($uidn);
            $courrier->setCategory($data['category']);

            // Upload fichier s’il existe
            if ($file) {
                $uploadedFilePath = $this->fileUploader->upload($file, "signature_file");
                $courrier->setSignature($uploadedFilePath);
            }

            // Validation
            $errors = $this->validator->validate($courrier);
            if (count($errors) > 0) {
                return $this->json([
                    'status' => 'error',
                    'message' => (string) $errors
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($courrier);
            $this->entityManager->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'Courrier créé avec succès',
                'data' => [
                    'id' => $courrier->getId(),
                    'objet' => $courrier->getObjet(),
                    'email' => $courrier->getEmailDestinateur()
                ]
            ], Response::HTTP_CREATED);

        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue : ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/list-courriers', name: 'app_list_courriers', methods: ['GET'])]
    public function listCourriers(Request $request): JsonResponse
    {
        try {
            // Récupération de l'utilisateur connecté
            $user = $this->tokenStorage->getToken()?->getUser();
            if (!$user || !is_object($user)) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Utilisateur non authentifié.'
                ], Response::HTTP_UNAUTHORIZED);
            }

            $companyId = $request->query->get('company_id');

            $qb = $this->entityManager->getRepository(Courriers::class)
                ->createQueryBuilder('c');

            

            //  Si l'utilisateur N'EST PAS ADMIN
            if (!in_array('ROLE_ADMIN', $user->getRoles(), true)) {

                //  Cas 1 : Employé simple
                if (in_array('ROLE_EMPLOYEE', $user->getRoles(), true) && count($user->getRoles()) === 1) {
                    $qb->where('c.user = :user')
                        ->setParameter('user', $user);
                } 
                //  Cas 2 : Autres rôles non admin (superviseur, manager, etc.)
                else {
                    $qb->where('c.company = :company')
                        ->setParameter('company', $user->getCompany());
                }
            }

            if (!empty($companyId)) {
                $qb->andWhere('c.company = :companyId')
                ->setParameter('companyId', $companyId);
            }

            $qb->orderBy('c.createdAt', 'DESC');

            $results = $qb->getQuery()->getResult();

            $data = array_map(function ($courrier) {
                return [
                    'id' => $courrier->getId(),
                    'objet' => $courrier->getObjet(),
                    'nom_destinateur' => $courrier->getNomDestinateur(),
                    'prenoms_destinateur' => $courrier->getPrenomsDestinateur(),
                    'contact_destinateur' => $courrier->getContactDestinateur(),
                    'email_destinateur' => $courrier->getEmailDestinateur(),
                    'adresse_destinateur' => $courrier->getAdresseDestinateur(),
                    'date' => $courrier->getDate()?->format('Y-m-d'),
                    'createdAt' => $courrier->getCreatedAt()?->format('Y-m-d H:i:s'),
                    'company' => $courrier->getCompany()?->getName(),
                    'signature' => $courrier->getSignature(),
                    'description' => $courrier->getDescription(),
                    'destinataire' => $courrier->getDestinataire(),
                    'lieu' => $courrier->getLieu(),
                    'status' => $courrier->getStatus(),
                    'validated_by_nom' => $courrier->getValidatedBy()?->getName(),
                    'validated_by_prenom' => $courrier->getValidatedBy()?->getFirstname(),
                    'uidn' => $courrier->getUidn(),
                    'category' => $courrier->getCategory(),
                ];
            }, $results);

            return $this->json([
                'status' => 'success',
                'message' => 'Liste des courriers',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des courriers : ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    //APPROVE COURRIER
    #[Route('/api/approve-courrier/{id}', name: 'app_approve_courrier', methods: ['POST'])]
    public function approveCourrier(
        int $id,
        CourriersRepository $courriersRepository,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): JsonResponse {
        try {
            // Récupération de l'utilisateur connecté
            $user = $this->tokenStorage->getToken()?->getUser();
            if (!$user || !is_object($user)) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Utilisateur non authentifié.'
                ], Response::HTTP_UNAUTHORIZED);
            }

            $courrier = $courriersRepository->find($id);
            if (!$courrier) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Courrier introuvable'
                ], Response::HTTP_NOT_FOUND);
            }

            // Vérification des permissions
            if ($courrier->getUser() !== $user && !(
                    in_array('ROLE_MANAGER', $user->getRoles(), true) || 
                    in_array('ROLE_ADMIN', $user->getRoles(), true)
                )) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Vous n\'avez pas la permission d\'approuver ce courrier.'
                ], Response::HTTP_FORBIDDEN);
            }


            // Approuver le courrier
            $courrier->setStatus(true);
            $courrier->setValidatedBy($user); // Le superviseur connecté
            $courrier->setUpdatedAt(new \DateTimeImmutable());
            $em->persist($courrier);
            $em->flush();

            //Envoi d'email (optionnel - exemple générique)
            $email = (new Email())
                ->from('no-reply@tonsite.com')
                ->to($courrier->getEmailDestinateur())
                ->subject('Votre courrier a été approuvé')
                ->html("
                    <p>Bonjour <strong>{$courrier->getNomDestinateur()} {$courrier->getPrenomsDestinateur()}</strong>,</p>
                    <p>Votre courrier du <strong>{$courrier->getDate()->format('d/m/Y')}</strong> a été approuvé.</p>
                    <p>Merci pour votre soumission.</p>
                ");

            $mailer->send($email);

            return $this->json([
                'status' => 'success',
                'message' => 'Courrier approuvé et email envoyé.'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue : ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/cancel-courrier/{id}', name: 'app_cancel_courrier', methods: ['POST'])]
    public function cancelCourrier(
        int $id,
        EntityManagerInterface $em,
        CourriersRepository $courriersRepository
    ): JsonResponse {
        $courrier = $courriersRepository->find($id);

        if (!$courrier) {
            return $this->json(['error' => 'Courrier introuvable'], 404);
        }

        $courrier->setStatus(null); // Annule la validation du courrier
        $courrier->setUpdatedAt(new \DateTimeImmutable());

        $em->persist($courrier);
        $em->flush();

        return $this->json(['message' => 'Courrier annulé avec succès.']);
    }

    //Editer le courrier
    #[Route('/api/courriers/{id}', name: 'app_courriers_show', methods: ['GET'])]
    public function show(Courriers $courrier): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => [
                'id' => $courrier->getId(),
                'objet' => $courrier->getObjet(),
                'nom_destinateur' => $courrier->getNomDestinateur(),
                'prenoms_destinateur' => $courrier->getPrenomsDestinateur(),
                'adresse_destinateur' => $courrier->getAdresseDestinateur(),
                'email_destinateur' => $courrier->getEmailDestinateur(),
                'contact_destinateur' => $courrier->getContactDestinateur(),
                'lieu' => $courrier->getLieu(),
                'date' => $courrier->getCreatedAt()?->format('Y-m-d'),
                'civilite' => $courrier->getCivilite(),
                'destinataire' => $courrier->getDestinataire(),
                'description' => $courrier->getDescription(),
                'category' => $courrier->getCategory(),
                'signature' => $courrier->getSignature(),
            ],
        ]);
    }

    //UPDATE COURRIER
    #[Route('/api/courriers/update/{id}', name: 'app_update_courrier', methods: ['POST'])]
    public function updateCourrier(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage
    ): JsonResponse {
        try {
            // Récupération des données envoyées en form-data
            $data = $request->request->all();
            $file = $request->files->get('signature_file');

            // Vérification des champs requis
            $requiredFields = [
                'objet', 'nom_destinateur', 'prenoms_destinateur', 'adresse_destinateur',
                'email_destinateur', 'contact_destinateur', 'lieu', 'date',
                'description', 'civilite', 'destinataire'
            ];

            $missingFields = $this->Helpers->validateRequiredFields($data, $requiredFields);
            if (!empty($missingFields)) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Champs manquants : ' . implode(', ', $missingFields)
                ], Response::HTTP_BAD_REQUEST);
            }

            // Récupération de l'utilisateur connecté
            $user = $tokenStorage->getToken()?->getUser();
            if (!$user || !is_object($user)) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Utilisateur non authentifié.'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Récupération du courrier
            $courrier = $em->getRepository(Courriers::class)->find($id);
            if (!$courrier) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Courrier non trouvé.'
                ], Response::HTTP_NOT_FOUND);
            }

            // Vérification des permissions
            if (
                $courrier->getUser() !== $user &&
                !in_array('ROLE_FRONT_DESK', $user->getRoles(), true) &&
                !in_array('ROLE_EMPLOYEE', $user->getRoles(), true) &&
                !in_array('ROLE_SUPERVISOR', $user->getRoles(), true) &&
                !in_array('ROLE_ADMIN', $user->getRoles(), true)
            ) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Vous n\'avez pas la permission de modifier ce courrier.'
                ], Response::HTTP_FORBIDDEN);
            }

            //  Mise à jour des champs du courrier
            $courrier->setObjet($data['objet']);
            $courrier->setNomDestinateur($data['nom_destinateur']);
            $courrier->setPrenomsDestinateur($data['prenoms_destinateur']);
            $courrier->setAdresseDestinateur($data['adresse_destinateur']);
            $courrier->setEmailDestinateur($data['email_destinateur']);
            $courrier->setContactDestinateur($data['contact_destinateur']);
            $courrier->setLieu($data['lieu']);
            $courrier->setDate(new \DateTime($data['date']));
            $courrier->setDescription($data['description']);
            $courrier->setCivilite($data['civilite']);
            $courrier->setDestinataire($data['destinataire']);
            $courrier->setCategory($data['category']);
            $courrier->setUpdatedAt(new \DateTimeImmutable());

            //  Gestion du fichier de signature
            if ($file) {
                $uploadedFilePath = $this->fileUploader->upload($file, "signature_file");
                $courrier->setSignature($uploadedFilePath);
            }

            $em->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'Courrier mis à jour avec succès.',
                'data' => [
                    'id' => $courrier->getId(),
                    'objet' => $courrier->getObjet(),
                    'updatedAt' => $courrier->getUpdatedAt()?->format('Y-m-d H:i:s'),
                ]
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour du courrier : ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


}