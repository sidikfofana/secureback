<?php

namespace App\Controller;

use App\Entity\Documents;
use App\Entity\Company;
use App\Helpers\Helpers;
use App\Repository\DocumentsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Services\FileUploader;

class DocumentsController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private Helpers $Helpers,
        private EntityManagerInterface $em,
        private MailerInterface $mailer,
        private TokenStorageInterface $tokenStorage,
        private UserRepository $userRepository,
        private FileUploader $fileUploader,
    ) {
    } 

    #[Route('/api/documents/list', name: 'app_list_documents', methods: ['GET'])]
    public function listDocuments(Request $request): JsonResponse
    {
        try {
            //Récupération de l'utilisateur connecté
            $user = $this->tokenStorage->getToken()?->getUser();
            if (!$user || !is_object($user)) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Utilisateur non authentifié.'
                ], Response::HTTP_UNAUTHORIZED);
            }

            //Récupération du company_id dans la requête GET
            $companyId = $request->query->get('company_id');

            //Construction de la requête Doctrine
            $qb = $this->entityManager->getRepository(Documents::class)
                ->createQueryBuilder('d');

            //Règles selon le rôle utilisateur
            if (!in_array('ROLE_ADMIN', $user->getRoles(), true)) {

                // Cas 1 : employé simple
                if (in_array('ROLE_EMPLOYEE', $user->getRoles(), true) && count($user->getRoles()) === 1) {
                    $qb->where('d.user = :user')
                       ->setParameter('user', $user);
                } 
                // Cas 2 : autre rôle non admin (superviseur, manager, etc.)
                else {
                    $qb->where('d.company = :company')
                       ->setParameter('company', $user->getCompany());
                }
            }

            //Si un filtre company_id est précisé
            if (!empty($companyId)) {
                $qb->andWhere('d.company = :companyId')
                   ->setParameter('companyId', $companyId);
            }

            $qb->orderBy('d.created_at', 'DESC');

            //Exécution de la requête
            $results = $qb->getQuery()->getResult();

            //Mise en forme des données pour la réponse JSON
            $data = array_map(function (Documents $doc) {
                return [
                    'id' => $doc->getId(),
                    'title' => $doc->getTitle(),
                    'description' => $doc->getDescription(),
                    'document_file' => $doc->getDocumentFile(),
                    'created_at' => $doc->getCreatedAt()?->format('Y-m-d H:i:s'),
                    'updated_at' => $doc->getUpdatedAt()?->format('Y-m-d H:i:s'),
                    'company' => $doc->getCompany()?->getName(),
                    'user_name' => $doc->getUser()?->getName(),
                    'user_firstname' => $doc->getUser()?->getFirstname(),
                    'validated_by_nom' => $doc->getValidatedBy()?->getName(),
                    'validated_by_prenom' => $doc->getValidatedBy()?->getFirstname(),
                    'status_validated' => $doc->getStatusValidated(),
                    'date_validated' => $doc->getDateValidated()?->format('Y-m-d'),
                    'approved_by_nom' => $doc->getApprovedBy()?->getName(),
                    'approved_by_prenom' => $doc->getApprovedBy()?->getFirstname(),
                    'status_approved' => $doc->getStatusApproved(),
                    'document_file' => $doc->getDocumentFile(),
                ];
            }, $results);

            //Réponse finale
            return $this->json([
                'status' => 'success',
                'message' => 'Liste des documents',
                'data' => $data
            ], Response::HTTP_OK, [], ['groups' => 'documents']);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des documents : ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/documents/create', name: 'app_create_documents', methods: ['POST'])]
    public function createDocuments(Request $request): JsonResponse
    {
        try {
            // Récupération des données et du fichier
            $data = $request->request->all();
            $file = $request->files->get('document_file');

            // Validation des champs requis
            $requiredFields = ['title', 'description'];
            $missingFields = $this->Helpers->validateRequiredFields($data, $requiredFields);

            if (!empty($missingFields)) {
                throw new \InvalidArgumentException('Champs manquants : ' . implode(', ', $missingFields));
            }

            // Vérifier si l'utilisateur est connecté
            $connectedUser = $this->tokenStorage->getToken()?->getUser();
            if (!$connectedUser || !is_object($connectedUser)) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Utilisateur non authentifié.',
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Récupérer l’utilisateur qui envoie le document (celui indiqué dans form-data)
            // $user = $this->userRepository->find($data['user_id']);
            // if (!$user) {
            //     return $this->json([
            //         'status' => 'error',
            //         'message' => 'Utilisateur non trouvé.'
            //     ], Response::HTTP_NOT_FOUND);
            // }

            // Création du document
            $document = new Documents();
            $document->setTitle($data['title']);
            $document->setDescription($data['description']);
            $document->setUser($connectedUser);
            $document->setCreatedAt(new \DateTimeImmutable());
            $document->setUpdatedAt(new \DateTimeImmutable());
            $document->setStatusValidated('pending'); 
            $document->setStatusApproved('pending'); 

            // Association à une entreprise si fourni
            if (!empty($data['company_id'])) {
                $company = $this->entityManager->getRepository(Company::class)->find($data['company_id']);
                if ($company) {
                    $document->setCompany($company);
                }
            }

            // Upload du fichier (optionnel)
            if ($file) {
                $uploadedFilePath = $this->fileUploader->upload($file, "document_file");
                $document->setDocumentFile($uploadedFilePath);
            }

            // Gestion de la validation / approbation (facultative)
            if (!empty($data['validated_by_id'])) {
                $validatedUser = $this->userRepository->find($data['validated_by_id']);
                if ($validatedUser) {
                    $document->setValidatedBy($validatedUser);
                    $document->setDateValidated(new \DateTime());
                }
            }

            if (!empty($data['approved_by_id'])) {
                $approvedUser = $this->userRepository->find($data['approved_by_id']);
                if ($approvedUser) {
                    $document->setApprovedBy($approvedUser);
                }
            }

            if (!empty($data['status_validated'])) {
                $document->setStatusValidated($data['status_validated']);
            }

            if (!empty($data['status_approved'])) {
                $document->setStatusApproved($data['status_approved']);
            }

            // Validation de l'entité
            $errors = $this->validator->validate($document);
            if (count($errors) > 0) {
                return $this->json([
                    'status' => 'error',
                    'message' => (string) $errors
                ], Response::HTTP_BAD_REQUEST);
            }

            // Enregistrement
            $this->entityManager->persist($document);
            $this->entityManager->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'Document créé avec succès',
                'data' => [
                    'id' => $document->getId(),
                    'title' => $document->getTitle(),
                    'description' => $document->getDescription(),
                    'file' => $document->getDocumentFile(),
                    'created_at' => $document->getCreatedAt()->format('Y-m-d H:i:s'),
                    'user' => [
                        'id' => $connectedUser->getId(),
                        'name' => $connectedUser->getName() ?? $connectedUser->getFirstname(),
                        'email' => $connectedUser->getEmail(),
                    ],
                    'company' => $document->getCompany()?->getName(),
                    'status_validated' => $document->getStatusValidated(),
                    'status_approved' => $document->getStatusApproved(),
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

    // Afficher un document
    #[Route('/api/documents/{id}', name: 'app_documents_show', methods: ['GET'])]
    public function show(Documents $document): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => [
                'id' => $document->getId(),
                'title' => $document->getTitle(),
                'description' => $document->getDescription(),
                'document_file' => $document->getDocumentFile(),
                'created_at' => $document->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updated_at' => $document->getUpdatedAt()?->format('Y-m-d H:i:s'),
                'validated_by_nom' => $document->getValidatedBy()?->getName() ?? null,
                'validated_by_prenom' => $document->getValidatedBy()?->getFirstname() ?? null,
                'status_validated' => $document->getStatusValidated(),
                'approved_by_nom' => $document->getApprovedBy()?->getName() ?? null,
                'approved_by_prenom' => $document->getApprovedBy()?->getFirstname() ?? null,
                'status_approved' => $document->getStatusApproved(),
            ],
        ]);
    }

    //Mettre à jour un document
    #[Route('/api/documents/update/{id}', name: 'app_documents_update', methods: ['POST'])]
    public function updateDocument(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage
    ): JsonResponse {
        try {
            // Récupération des données envoyées
            $data = $request->request->all();
            $file = $request->files->get('document_file');

            // Champs requis
            $requiredFields = ['title', 'description'];
            $missingFields = $this->Helpers->validateRequiredFields($data, $requiredFields);
            if (!empty($missingFields)) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Champs manquants : ' . implode(', ', $missingFields)
                ], Response::HTTP_BAD_REQUEST);
            }

            // Vérification utilisateur connecté
            $user = $tokenStorage->getToken()?->getUser();
            if (!$user || !is_object($user)) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Utilisateur non authentifié.'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Récupération du document
            $document = $em->getRepository(Documents::class)->find($id);
            if (!$document) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Document non trouvé.'
                ], Response::HTTP_NOT_FOUND);
            }

            // Vérification des permissions
            $userRoles = $user->getRoles();
            if (
                $document->getUser() !== $user &&
                !in_array('ROLE_EMPLOYEE', $userRoles, true) &&
                !in_array('ROLE_SUPERVISOR', $userRoles, true) &&
                !in_array('ROLE_ADMIN', $userRoles, true)
            ) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Vous n\'avez pas la permission de modifier ce document.'
                ], Response::HTTP_FORBIDDEN);
            }

            // Mise à jour des champs
            $document->setTitle($data['title']);
            $document->setDescription($data['description']);
            $document->setUpdatedAt(new \DateTimeImmutable());
            $document->setStatusValidated('validated');
            $document->setValidatedBy($user);
            // Gestion du fichier uploadé
            if ($file) {
                $uploadedFilePath = $this->fileUploader->upload($file, "internal_documents");
                $document->setDocumentFile($uploadedFilePath);
            }

            // Sauvegarde en base
            $em->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'Document mis à jour avec succès.',
                'data' => [
                    'id' => $document->getId(),
                    'title' => $document->getTitle(),
                    'updated_at' => $document->getUpdatedAt()?->format('Y-m-d H:i:s'),
                ]
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour du document : ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    //APPROVE COURRIER
    #[Route('/api/documents/approve/{id}', name: 'app_approve_document', methods: ['POST'])]
    public function approveCourrier(
        int $id,
        DocumentsRepository $documentsRepository,
        EntityManagerInterface $em,
        //MailerInterface $mailer
    ): JsonResponse {
        try {
            // Récupération de l'utilisateur connecté
            $connectedUser = $this->tokenStorage->getToken()?->getUser();
            if (!$connectedUser || !is_object($connectedUser)) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Utilisateur non authentifié.',
                ], Response::HTTP_UNAUTHORIZED);
            }

            $document = $documentsRepository->find($id);
            if (!$document) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Document introuvable'
                ], Response::HTTP_NOT_FOUND);
            }

            // Vérification des permissions
            if ($document->getUser() !== $connectedUser && !(
                    in_array('ROLE_MANAGER', $connectedUser->getRoles(), true) || 
                    in_array('ROLE_ADMIN', $connectedUser->getRoles(), true)
                )) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Vous n\'avez pas la permission d\'approuver ce courrier.'
                ], Response::HTTP_FORBIDDEN);
            }


            // Approuver le Document
            $document->setStatusApproved('approved');
            $document->setApprovedBy($connectedUser); // Le superviseur connecté
            $document->setDateValidated(new \DateTimeImmutable());
            $em->persist($document);
            $em->flush();

            //Envoi d'email (optionnel - exemple générique)
            // $email = (new Email())
            //     ->from('no-reply@tonsite.com')
            //     ->to($courrier->getEmailDestinateur())
            //     ->subject('Votre courrier a été approuvé')
            //     ->html("
            //         <p>Bonjour <strong>{$courrier->getNomDestinateur()} {$courrier->getPrenomsDestinateur()}</strong>,</p>
            //         <p>Votre courrier du <strong>{$courrier->getDate()->format('d/m/Y')}</strong> a été approuvé.</p>
            //         <p>Merci pour votre soumission.</p>
            //     ");

            // $mailer->send($email);

            return $this->json([
                'status' => 'success',
                'message' => 'Document approuvé et email envoyé.'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue : ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/documents/cancel/{id}', name: 'app_cancel_document', methods: ['POST'])]
    public function cancelCourrier(
        int $id,
        EntityManagerInterface $em,
        DocumentsRepository $documentsRepository
    ): JsonResponse {
        $document = $documentsRepository->find($id);

        if (!$document) {
            return $this->json(['error' => 'Document introuvable'], 404);
        }

        // Vérifier si l'utilisateur est connecté
        $connectedUser = $this->tokenStorage->getToken()?->getUser();
        if (!$connectedUser || !is_object($connectedUser)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Utilisateur non authentifié.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $document->setStatusApproved('rejected'); // Annule la validation du document
        $document->setApprovedBy($connectedUser); // Le superviseur connecté
        $document->setDateValidated(new \DateTimeImmutable());

        $em->persist($document);
        $em->flush();

        return $this->json(['message' => 'Document rejété avec succès.']);
    }

}
