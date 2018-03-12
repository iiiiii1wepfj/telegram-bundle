<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Subscriber;


use Kettari\TelegramBundle\Entity\Chat;
use Kettari\TelegramBundle\Telegram\Event\MessageReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use unreal4u\TelegramAPI\Telegram\Types\Chat as TelegramChat;

class ChatSubscriber extends AbstractBotSubscriber implements EventSubscriberInterface
{
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
    return [MessageReceivedEvent::NAME => ['onMessageReceived', 90000]];
  }

  /**
   * Updates User and Chat for the current user; adds default roles if 1st
   * message ever.
   *
   * @param MessageReceivedEvent $event
   */
  public function onMessageReceived(MessageReceivedEvent $event)
  {
    // Update the chat
    $this->updateChat($event->getMessage()->chat);
  }

  /**
   * Returns Chat object. Optionally it is added to persist if changes detected.
   *
   * @param TelegramChat $telegramChat
   */
  private function updateChat($telegramChat)
  {
    // Find chat object. If not found, create new
    $chat = $this->doctrine->getRepository('KettariTelegramBundle:Chat')
      ->findOneByTelegramId($telegramChat->id);
    if (!$chat) {
      $chat = new Chat();
      $this->doctrine->getManager()
        ->persist($chat);
    }
    // Update information
    $chat->setTelegramId($telegramChat->id)
      ->setFirstName($telegramChat->first_name)
      ->setLastName($telegramChat->last_name)
      ->setUsername($telegramChat->username)
      ->setType($telegramChat->type)
      ->setTitle($telegramChat->title)
      ->setAllMembersAreAdministrators(
        $telegramChat->all_members_are_administrators
      );

    // Commit changes
    $this->doctrine->getManager()
      ->flush();
  }

}