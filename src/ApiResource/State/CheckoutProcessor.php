<?php
namespace App\ApiResource\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\CheckoutRequest;
use App\Dto\CheckoutResult;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use ApiPlatform\Symfony\Validator\Exception\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

final class CheckoutProcessor implements ProcessorInterface
{
    public function __construct(
        private ValidatorInterface $validator,
        private MailerInterface $mailer,
        #[Autowire('%env(MAILER_FROM)%')] private string $from,
        #[Autowire('%env(MAILER_INTERNAL)%')] private string $internalTo,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CheckoutResult
    {
        if (!$data instanceof CheckoutRequest) {
            $violations = $this->validator->validate($data);
            throw new ValidationException($violations);
        }
        $violations = $this->validator->validate($data);
        if (count($violations) > 0) {
            throw new ValidationException($violations);
        }

        $orderNo = 'R-' . date('ymd') . '-' . random_int(1000, 9999);

        $emailToCustomer = (new TemplatedEmail())
            ->from($this->from)->to($data->email)
            ->subject('Rendelés visszaigazolás - ' . $orderNo)
            ->htmlTemplate('emails/order_confirmation.html.twig')
            ->context(['o' => $data, 'orderNo' => $orderNo]);

        $emailInternal = (new TemplatedEmail())
            ->from($this->from)->to($this->internalTo)
            ->subject('Új rendelés - ' . $orderNo)
            ->htmlTemplate('emails/order_internal.html.twig')
            ->context(['o' => $data, 'orderNo' => $orderNo]);

        try {
            $this->mailer->send($emailToCustomer);
        } catch (TransportExceptionInterface) {
        }

        usleep(1_200_000);

        try {
            $this->mailer->send($emailInternal);
        } catch (TransportExceptionInterface) {
        }

        return new CheckoutResult(true, $orderNo);
    }
}
