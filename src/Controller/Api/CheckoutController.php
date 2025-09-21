<?php
namespace App\Controller\Api;

use App\Dto\CheckoutRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;


#[Route('/api/checkout', name: 'api_checkout_')]
class CheckoutController extends AbstractController
{
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $req,
        ValidatorInterface $validator,
        MailerInterface $mailer,
        #[Autowire('%env(MAILER_FROM)%')] string $from,
        #[Autowire('%env(MAILER_INTERNAL)%')] string $internalTo,
    ): JsonResponse {
        $data = json_decode($req->getContent() ?: '[]', true);
        $dto  = CheckoutRequest::fromArray($data);

        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            $errs = [];
            foreach ($errors as $e) {
                $errs[] = [
                    'field' => $e->getPropertyPath(),
                    'msg'   => $e->getMessage()
                ];
            }
            return $this->json(['errors' => $errs], 422);
        }

        $orderNo = 'R-' . date('ymd') . '-' . random_int(1000, 9999);

        $emailToCustomer = (new TemplatedEmail())
            ->from($from)
            ->to($dto->email)
            ->subject('Rendelés visszaigazolás - ' . $orderNo)
            ->htmlTemplate('emails/order_confirmation.html.twig')
            ->context(['o' => $dto, 'orderNo' => $orderNo]);

        $emailInternal = (new TemplatedEmail())
            ->from($from)
            ->to($internalTo)
            ->subject('Új rendelés - ' . $orderNo)
            ->htmlTemplate('emails/order_internal.html.twig')
            ->context(['o' => $dto, 'orderNo' => $orderNo]);

        $mailer->send($emailToCustomer);
        $mailer->send($emailInternal);

        return $this->json(['ok' => true, 'orderNo' => $orderNo]);
    }
}
