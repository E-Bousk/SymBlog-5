<?php

namespace App\Form\FormExtension;

use Psr\Log\LoggerInterface;
use Symfony\Component\Form\AbstractType;
use App\EventSubscriber\HoneyPotSubscriber;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class HoneyPotType extends AbstractType
{
    // AutoWire le service 'HoneyPotLogger' avec « $honeyPotLogger »
    // voir « symfony console debug:autowiring log »
    // ==> « Psr\Log\LoggerInterface $honeyPotLogger (monolog.logger.honey_pot) »
    private LoggerInterface $honeyPotLogger;
    private RequestStack $requestStack;

    public function __construct(LoggerInterface $honeyPotLogger, RequestStack $requestStack)
    {
        $this->honeyPotLogger = $honeyPotLogger;
        $this->requestStack = $requestStack;
    }

    protected const HONEYPOT_FOR_BOT = "phone";
    protected const CANDYPOT_FOR_BOT = "faxNumber";

    /** 
     * Build a form with HTML attributes and add an EventSubscriber.
     * 
     * @param FormBuilderInterface<callable> $builder
     * @param array<mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(self::HONEYPOT_FOR_BOT, TextType::class, $this->setHoneyPotFieldConfiguration(self::HONEYPOT_FOR_BOT))
            ->add(self::CANDYPOT_FOR_BOT, TextType::class, $this->setHoneyPotFieldConfiguration(self::CANDYPOT_FOR_BOT))
            ->addEventSubscriber(new HoneyPotSubscriber($this->honeyPotLogger, $this->requestStack))
        ;
    }

    /** 
     * Set field attributes to HoneyPot
     * 
     * @param string $label
     * @return array<mixed>
     */
    protected function setHoneyPotFieldConfiguration(string $label): array
    {
        if ($label === 'phone') {
            $label = "Téléphone";
        }

        if ($label === 'faxNumber') {
            $label = "Numéro de fax";
        }

        return [
            'label' => $label,
            'attr' => [
                'autocomplete' =>  'off',
                'tabindex' => '-1'
            ],
            // 'data' => 'fake data', // ‼ NOTE : Ne sert que pour tester ‼
            'mapped' => false,
            'required' => false
        ];
    }
}