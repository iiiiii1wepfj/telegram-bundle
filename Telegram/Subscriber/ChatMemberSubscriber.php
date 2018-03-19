<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Subscriber;


use Kettari\TelegramBundle\Entity\Chat;
use Kettari\TelegramBundle\Entity\ChatMember;
use Kettari\TelegramBundle\Telegram\Event\JoinChatMemberBotEvent;
use Kettari\TelegramBundle\Telegram\Event\JoinChatMemberEvent;
use Kettari\TelegramBundle\Telegram\Event\JoinChatMembersManyEvent;
use Kettari\TelegramBundle\Telegram\Event\LeftChatMemberBotEvent;
use Kettari\TelegramBundle\Telegram\Event\LeftChatMemberEvent;
use Kettari\TelegramBundle\Telegram\Event\TextReceivedEvent;
use Kettari\TelegramBundle\Telegram\UserHqInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\Telegram\Types\User as TelegramUser;

class ChatMemberSubscriber extends AbstractBotSubscriber implements EventSubscriberInterface
{
  /**
   * @var RegistryInterface
   */
  protected $doctrine;

  /**
   * @var EventDispatcherInterface
   */
  private $dispatcher;

  /**
   * @var UserHqInterface
   */
  private $userHq;

  /**
   * ChatMemberSubscriber constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Symfony\Bridge\Doctrine\RegistryInterface $doctrine
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   * @param \Kettari\TelegramBundle\Telegram\UserHqInterface $userHq
   */
  public function __construct(
    LoggerInterface $logger,
    RegistryInterface $doctrine,
    EventDispatcherInterface $dispatcher,
    UserHqInterface $userHq
  ) {
    parent::__construct($logger);
    $this->doctrine = $doctrine;
    $this->dispatcher = $dispatcher;
    $this->userHq = $userHq;
  }

  /**
   * Returns an array of event names this subscriber wants to listen to.
   *
   * The array keys are event names and the value can be:
   *
   *  * The method name to call (priority defaults to 0)
   *  * An array composed of the method name to call and the priority
   *  * An array of arrays composed of the method names to call and respective
   *    priorities, or 0 if unset
   *
   * For instance:
   *
   *  * array('eventName' => 'methodName')
   *  * array('eventName' => array('methodName', $priority))
   *  * array('eventName' => array(array('methodName1', $priority),
   * array('methodName2')))
   *
   * @return array The event names to listen to
   */
  public static function getSubscribedEvents()
  {
    return [
      JoinChatMembersManyEvent::NAME => ['onMembersManyJoined', 90000],
      JoinChatMemberEvent::NAME      => ['onMemberJoined', 90000],
      LeftChatMemberEvent::NAME      => ['onMemberLeft', 90000],
      TextReceivedEvent::NAME        => ['onMemberTexted', 90000],
    ];
  }

  /**
   * When somebody joined the chat, updates database.
   *
   * @param JoinChatMembersManyEvent $event
   */
  public function onMembersManyJoined(JoinChatMembersManyEvent $event)
  {
    $this->logger->debug(
      'Processing ChatMemberSubscriber::JoinChatMembersManyEvent for the message ID={message_id}',
      ['message_id' => $event->getMessage()->message_id]
    );

    /** @var Chat $chatEntity */
    $chatEntity = $this->doctrine->getRepository('KettariTelegramBundle:Chat')
      ->findOneByTelegramId($event->getMessage()->chat->id);
    $users = $event->getMessage()->new_chat_members;

    /** @var TelegramUser $joinedUser */
    foreach ($users as $joinedUser) {
      $joinedMemberEvent = new JoinChatMemberEvent(
        $event->getUpdate(), $joinedUser, $chatEntity
      );
      $this->dispatcher->dispatch(
        JoinChatMemberEvent::NAME,
        $joinedMemberEvent
      );
    }
    // Flush changes
    $this->doctrine->getManager()
      ->flush();

    // Tell the Bot this request is handled
    $event->getKeeper()
      ->setRequestHandled(true);

    $this->logger->info(
      'ChatMemberSubscriber::JoinChatMembersManyEvent for the message ID={message_id} processed',
      ['message_id' => $event->getMessage()->message_id]
    );
  }

  /**
   * When somebody joined the chat, updates database.
   *
   * @param JoinChatMemberEvent $event
   */
  public function onMemberJoined(JoinChatMemberEvent $event)
  {
    $this->logger->debug(
      'Processing ChatMemberSubscriber::JoinChatMemberEvent for the message ID={message_id}',
      ['message_id' => $event->getMessage()->message_id]
    );

    $this->processJoinedMember(
      $event->getUpdate(),
      $event->getJoinedUser(),
      $event->getChatEntity()
    );
    // Flush changes
    $this->doctrine->getManager()
      ->flush();

    $this->logger->info(
      'ChatMemberSubscriber::JoinChatMemberEvent for the message ID={message_id} processed',
      ['message_id' => $event->getMessage()->message_id]
    );
  }

  /**
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $telegramUser
   * @param Chat $chatEntity
   */
  private function processJoinedMember(
    Update $update,
    TelegramUser $telegramUser,
    Chat $chatEntity
  ) {
    $this->logger->debug(
      'Processing joined member user ID={user_id} for the message ID={message_id}',
      [
        'user_id'    => $telegramUser->id,
        'message_id' => $update->message->message_id,
      ]
    );

    // Find user object. If not found, create new
    if (is_null(
      $userEntity = $this->doctrine->getRepository('KettariTelegramBundle:User')
        ->findOneByTelegramId($telegramUser->id)
    )) {
      $userEntity = $this->userHq->createAnonymousUser($telegramUser);
    }

    // Find chat member object. If not found, create new
    $chatMember = $this->doctrine->getRepository(
      'KettariTelegramBundle:ChatMember'
    )
      ->findOneByChatAndUser($chatEntity, $userEntity);
    if (!$chatMember) {
      $chatMember = new ChatMember();
      $this->doctrine->getManager()
        ->persist($chatMember);
    }
    $chatMember->setChat($chatEntity)
      ->setUser($userEntity)
      ->setJoinDate(new \DateTime('now', new \DateTimeZone('UTC')))
      ->setLeaveDate(null)
      ->setStatus('member');

    // Check if joined user is the bot itself and dispatch appropriate event if yes
    /** @var \Kettari\TelegramBundle\Entity\BotSetting $botSetting_UserId */
    $botSetting_UserId = $this->doctrine->getRepository(
      'KettariTelegramBundle:BotSetting'
    )
      ->findOneByName('bot_user_id');
    if (is_null($botSetting_UserId)) {
      throw new \LogicException(
        'Bot user ID is not configured ("bot_user_id" settings must be present).'
      );
    }
    if ($botSetting_UserId->getValue() == $telegramUser->id) {
      $this->dispatchJoinedBotEvent(
        $update,
        $telegramUser,
        $chatEntity,
        $userEntity
      );
    }

    $this->logger->debug(
      'JOIN: self_user_id={self_user_id}, telegram_user_id={telegram_user_id}',
      [
        'self_user_id'     => $botSetting_UserId->getValue(),
        'telegram_user_id' => $telegramUser->id,
      ]
    );
    $this->logger->info(
      'Joined member user ID={user_id} for the message ID={message_id} processed',
      [
        'user_id'    => $telegramUser->id,
        'message_id' => $update->message->message_id,
      ]
    );
  }

  /**
   * @param Update $update
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $joinedUser
   * @param \Kettari\TelegramBundle\Entity\Chat
   * @param \Kettari\TelegramBundle\Entity\User
   */
  private function dispatchJoinedBotEvent(
    $update,
    $joinedUser,
    $chatEntity,
    $userEntity
  ) {
    $botJoinedEvent = new JoinChatMemberBotEvent(
      $update, $joinedUser, $chatEntity, $userEntity
    );
    $this->dispatcher->dispatch(JoinChatMemberBotEvent::NAME, $botJoinedEvent);
  }

  /**
   * When somebody left the chat, updates database.
   *
   * @param LeftChatMemberEvent $event
   */
  public function onMemberLeft(LeftChatMemberEvent $event)
  {
    $this->logger->debug(
      'Processing ChatMemberSubscriber::LeftChatMemberEvent for the message ID={message_id}',
      ['message_id' => $event->getMessage()->message_id]
    );

    /** @var Chat $chat */
    $chat = $this->doctrine->getRepository('KettariTelegramBundle:Chat')
      ->findOneByTelegramId($event->getMessage()->chat->id);
    $this->processLeftMember(
      $event->getUpdate(),
      $event->getMessage()->left_chat_member,
      $chat
    );
    // Flush changes
    $this->doctrine->getManager()
      ->flush();

    // Tell the Bot this request is handled
    $event->getKeeper()
      ->setRequestHandled(true);

    $this->logger->info(
      'ChatMemberSubscriber::LeftChatMemberEvent for the message ID={message_id} processed',
      ['message_id' => $event->getMessage()->message_id]
    );
  }

  /**
   * @param \unreal4u\TelegramAPI\Telegram\Types\Update $update
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $telegramUser
   * @param Chat $chatEntity
   */
  private function processLeftMember(
    Update $update,
    TelegramUser $telegramUser,
    Chat $chatEntity
  ) {
    $this->logger->debug(
      'Processing left member user ID={user_id} for the message ID={message_id}',
      [
        'user_id'    => $telegramUser->id,
        'message_id' => $update->message->message_id,
      ]
    );

    // Find user object. If not found, create new
    $userEntity = $this->doctrine->getRepository('KettariTelegramBundle:User')
      ->findOneByTelegramId($telegramUser->id);
    if (!$userEntity) {
      $userEntity = $this->userHq->createAnonymousUser($telegramUser);
    }

    // Find chat member object. If not found, create new
    $chatMember = $this->doctrine->getRepository(
      'KettariTelegramBundle:ChatMember'
    )
      ->findOneByChatAndUser($chatEntity, $userEntity);
    if (!$chatMember) {
      $chatMember = new ChatMember();
      $this->doctrine->getManager()
        ->persist($chatMember);
    }
    $chatMember->setChat($chatEntity)
      ->setUser($userEntity)
      ->setLeaveDate(new \DateTime('now', new \DateTimeZone('UTC')))
      ->setStatus('left');

    // Check if left user is the bot itself and dispatch appropriate event if yes
    /** @var \Kettari\TelegramBundle\Entity\BotSetting $botSetting_UserId */
    $botSetting_UserId = $this->doctrine->getRepository(
      'KettariTelegramBundle:BotSetting'
    )
      ->findOneByName('bot_user_id');
    if (is_null($botSetting_UserId)) {
      throw new \LogicException(
        'Bot user ID is not configured ("bot_user_id" settings must be present).'
      );
    }
    if ($botSetting_UserId->getValue() == $telegramUser->id) {
      $this->dispatchLeftBotEvent(
        $update,
        $telegramUser,
        $chatEntity,
        $userEntity
      );
    }

    $this->logger->debug(
      'LEFT: self_user_id={self_user_id}, telegram_user_id={telegram_user_id}',
      [
        'self_user_id'     => $botSetting_UserId->getValue(),
        'telegram_user_id' => $telegramUser->id,
      ]
    );
    $this->logger->info(
      'Left member user ID={user_id} for the message ID={message_id} processed',
      [
        'user_id'    => $telegramUser->id,
        'message_id' => $update->message->message_id,
      ]
    );
  }

  /**
   * @param Update $update
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $leftUser
   * @param \Kettari\TelegramBundle\Entity\Chat
   * @param \Kettari\TelegramBundle\Entity\User
   */
  private function dispatchLeftBotEvent(
    $update,
    $leftUser,
    $chatEntity,
    $userEntity
  ) {
    $botLeftEvent = new LeftChatMemberBotEvent(
      $update, $leftUser, $chatEntity, $userEntity
    );
    $this->dispatcher->dispatch(LeftChatMemberBotEvent::NAME, $botLeftEvent);
  }

  /**
   * When somebody posted text in the chat, updates database.
   *
   * @param TextReceivedEvent $event
   */
  public function onMemberTexted(TextReceivedEvent $event)
  {
    $this->logger->debug(
      'Processing ChatMemberSubscriber::TextReceivedEvent for the message ID={message_id}',
      ['message_id' => $event->getMessage()->message_id,]
    );

    /** @var Chat $chat */
    $chat = $this->doctrine->getRepository('KettariTelegramBundle:Chat')
      ->findOneByTelegramId($event->getMessage()->chat->id);
    $this->processTextedMember($event->getMessage()->from, $chat);
    // Flush changes
    $this->doctrine->getManager()
      ->flush();

    // NB: This handler SHALL NOT mark request as handled. We just update ChatMember
    // silently

    $this->logger->info(
      'ChatMemberSubscriber::TextReceivedEvent for the message ID={message_id} processed',
      ['message_id' => $event->getMessage()->message_id,]
    );
  }

  /**
   * Creates user that sent text to the chat.
   *
   * @param \unreal4u\TelegramAPI\Telegram\Types\User $telegramUser
   * @param Chat $chat
   */
  private function processTextedMember(
    TelegramUser $telegramUser,
    Chat $chat
  ) {
    // Find user object. If not found, create new
    $userEntity = $this->doctrine->getRepository('KettariTelegramBundle:User')
      ->findOneByTelegramId($telegramUser->id);
    if (!$userEntity) {
      $userEntity = $this->userHq->createAnonymousUser($telegramUser);
    }

    // Find chat member object. If not found, create new
    $chatMember = $this->doctrine->getRepository(
      'KettariTelegramBundle:ChatMember'
    )
      ->findOneByChatAndUser($chat, $userEntity);
    if (!$chatMember) {
      $chatMember = new ChatMember();
      $this->doctrine->getManager()
        ->persist($chatMember);
    }
    $chatMember->setChat($chat)
      ->setUser($userEntity)
      ->setStatus('member');
  }

}