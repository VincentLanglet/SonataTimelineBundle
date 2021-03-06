<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\TimelineBundle\Block;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Block\Service\AbstractAdminBlockService;
use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\Form\Type\ImmutableArrayType;
use Spy\Timeline\Driver\ActionManagerInterface;
use Spy\Timeline\Driver\TimelineManagerInterface;
use Spy\Timeline\Model\TimelineInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class TimelineBlock extends AbstractAdminBlockService
{
    /**
     * @var ActionManagerInterface
     */
    protected $actionManager;

    /**
     * @var TimelineManagerInterface
     */
    protected $timelineManager;

    /**
     * @var TokenStorageInterface
     */
    protected $securityContext;

    /**
     * @param string $name
     */
    public function __construct($name, EngineInterface $templating, ActionManagerInterface $actionManager, TimelineManagerInterface $timelineManager, TokenStorageInterface $tokenStorage)
    {
        $this->actionManager = $actionManager;
        $this->timelineManager = $timelineManager;
        $this->securityContext = $tokenStorage;

        parent::__construct($name, $templating);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(BlockContextInterface $blockContext, ?Response $response = null)
    {
        $token = $this->securityContext->getToken();

        if (!$token) {
            return new Response();
        }

        $subject = $this->actionManager->findOrCreateComponent($token->getUser(), $token->getUser()->getId());

        $entries = $this->timelineManager->getTimeline($subject, [
            'page' => 1,
            'max_per_page' => $blockContext->getSetting('max_per_page'),
            'type' => TimelineInterface::TYPE_TIMELINE,
            'context' => $blockContext->getSetting('context'),
            'filter' => $blockContext->getSetting('filter'),
            'group_by_action' => $blockContext->getSetting('group_by_action'),
            'paginate' => $blockContext->getSetting('paginate'),
        ]);

        return $this->renderPrivateResponse($blockContext->getTemplate(), [
            'context' => $blockContext,
            'settings' => $blockContext->getSettings(),
            'block' => $blockContext->getBlock(),
            'entries' => $entries,
        ], $response);
    }

    /**
     * {@inheritdoc}
     */
    public function buildEditForm(FormMapper $formMapper, BlockInterface $block)
    {
        $formMapper->add('settings', ImmutableArrayType::class, [
            'keys' => [
                ['title', TextType::class, [
                    'label' => 'form.label_title',
                    'required' => false,
                ]],
                ['translation_domain', TextType::class, [
                    'label' => 'form.label_translation_domain',
                    'required' => false,
                ]],
                ['icon', TextType::class, [
                    'label' => 'form.label_icon',
                    'required' => false,
                ]],
                ['class', TextType::class, [
                    'label' => 'form.label_class',
                    'required' => false,
                ]],
                ['max_per_page', IntegerType::class, [
                    'required' => true,
                    'label' => 'form.label_max_per_page',
                ]],
            ],
            'translation_domain' => 'SonataTimelineBundle',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Timeline';
    }

    /**
     * {@inheritdoc}
     */
    public function configureSettings(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'max_per_page' => 10,
            'title' => null,
            'translation_domain' => null,
            'icon' => 'fa fa-clock-o fa-fw',
            'class' => null,
            'template' => '@SonataTimeline/Block/timeline.html.twig',
            'context' => 'GLOBAL',
            'filter' => true,
            'group_by_action' => true,
            'paginate' => true,
        ]);
    }
}
