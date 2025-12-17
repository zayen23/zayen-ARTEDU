<?php

namespace App\Validator\Constraints;

use App\Entity\Ticket;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueSeatNumberValidator extends ConstraintValidator
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function validate($ticket, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueSeatNumber) {
            throw new UnexpectedTypeException($constraint, UniqueSeatNumber::class);
        }

        if (!$ticket instanceof Ticket) {
            return;
        }

        // Si le numéro de siège n'est pas défini, on ne fait rien
        if (null === $ticket->getSeatNumber() || '' === $ticket->getSeatNumber()) {
            return;
        }

        // Vérifier si un autre ticket avec le même numéro de siège existe pour le même événement
        $existingTicket = $this->entityManager->getRepository(Ticket::class)
            ->createQueryBuilder('t')
            ->where('t.seatNumber = :seatNumber')
            ->andWhere('t.event = :event')
            ->andWhere('t.id != :id')
            ->setParameter('seatNumber', $ticket->getSeatNumber())
            ->setParameter('event', $ticket->getEvent())
            ->setParameter('id', $ticket->getId() ?? 0)
            ->getQuery()
            ->getOneOrNullResult();

        if (null !== $existingTicket) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $ticket->getSeatNumber())
                ->atPath('seatNumber')
                ->addViolation();
        }
    }
}

