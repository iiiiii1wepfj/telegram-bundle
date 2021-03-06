<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 16.03.2017
 * Time: 18:18
 */

namespace Kaula\TelegramBundle\Telegram\Command;


use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;
use unreal4u\TelegramAPI\Telegram\Types\KeyboardButton;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardMarkup;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardRemove;

class SettingsCommand extends AbstractCommand
{

  static public $name = 'settings';
  static public $description = 'Настройки бота';
  static public $required_permissions = ['execute command settings'];

  const BTN_NOTIFICATION = 'Уведомления';
  const BTN_CANCEL = 'Ничего не менять';

  const MARK_V = "\xE2\x9C\x85";
  const MARK_X = "\xE2\x9D\x8C";

  /**
   * Executes command.
   */
  public function execute()
  {
    if ('private' == $this->getUpdate()->message->chat->type) {
      $this->replyWithMessage(
        'Какие настройки бота вы хотите изменить?',
        '',
        $this->getReplyKeyboardMarkup_MainMenu()
      );

      $this->getBus()
        ->getHooker()
        ->createHook(
          $this->getUpdate(),
          get_class($this),
          'handleSettingsMainMenu'
        );
    } else {
      $this->replyWithMessage(
        'Эта команда работает только в личной переписке с ботом. В общем канале управление настройками невозможно.'
      );
    }
  }

  /**
   * Returns reply markup object.
   *
   * @return ReplyKeyboardMarkup
   */
  private function getReplyKeyboardMarkup_MainMenu()
  {
    // Notification button
    $notification_btn = new KeyboardButton();
    $notification_btn->text = self::BTN_NOTIFICATION;

    // Cancel button
    $cancel_btn = new KeyboardButton();
    $cancel_btn->text = self::BTN_CANCEL;

    // Keyboard
    $reply_markup = new ReplyKeyboardMarkup();
    $reply_markup->one_time_keyboard = true;
    $reply_markup->resize_keyboard = true;
    $reply_markup->keyboard[][] = $notification_btn;
    $reply_markup->keyboard[][] = $cancel_btn;

    return $reply_markup;
  }

  /**
   * Handles /settings main menu.
   */
  public function handleSettingsMainMenu()
  {
    $message = $this->getUpdate()->message;
    if (is_null($message)) {
      return;
    }

    switch ($message->text) {
      case self::BTN_NOTIFICATION:
        $this->replyWithMessage(
          'Отметьте уведомления, которые хотите получать:',
          '',
          $this->getReplyKeyboardMarkup_Notifications()
        );
        $this->getBus()
          ->getHooker()
          ->createHook(
            $this->getUpdate(),
            get_class($this),
            'handleSettingsNotificationOption'
          );
        break;
      case self::BTN_CANCEL:
        $this->replyWithMessage(
          'Команда отменена.',
          '',
          new ReplyKeyboardRemove()
        );
        break;
      default:
        $this->replyWithMessage(
          'Вы прислали непонятный мне вариант меню. Попробуйте ещё раз /settings',
          '',
          new ReplyKeyboardRemove()
        );
        break;
    }
  }

  /**
   * Returns reply markup for notifications option.
   *
   * @return \unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup
   */
  private function getReplyKeyboardMarkup_Notifications()
  {
    // Load user's permissions and notifications
    $user_permissions = $this->getBus()
      ->getBot()
      ->getUserHq()
      ->getUserPermissions();
    $user_notifications = $this->getBus()
      ->getBot()
      ->getUserHq()
      ->getUserNotifications();

    $d = $this->getBus()
      ->getBot()
      ->getContainer()
      ->get('doctrine');

    // Load notifications
    /** @var \Kaula\TelegramBundle\Entity\Notification $notifications */
    $notifications = $d->getRepository('KaulaTelegramBundle:Notification')
      ->findBy([], ['sort_order' => 'ASC']);
    // Check if user has required for each notification permission
    $inline_keyboard = new Markup();
    /** @var \Kaula\TelegramBundle\Entity\Notification $notification_item */
    foreach ($notifications as $notification_item) {
      $row = [];
      if ($user_permissions->contains($notification_item->getPermission())) {

        // Does user have this notification enabled?
        $mark = ($user_notifications->contains(
          $notification_item
        )) ? self::MARK_V : self::MARK_X;

        $inline_keyboard_button = new Button();
        $inline_keyboard_button->text = $mark.' '.
          $notification_item->getTitle();
        $inline_keyboard_button->callback_data = $notification_item->getName();
        $row[] = $inline_keyboard_button;
      }
      if (count($row)) {
        $inline_keyboard->inline_keyboard[] = $row;
      }
    }

    return $inline_keyboard;
  }

  /**
   * Handles selection of the Notification option in the Settings
   */
  public function handleSettingsNotificationOption()
  {
    $cq = $this->getUpdate()->callback_query;
    if (is_null($cq) && !is_null($this->getUpdate()->message)) {
      $this->replyWithMessage('Команда отменена.');
      return;
    }
    if (is_null($cq) || is_null($cq->message)) {
      return;
    }

    $hq = $this->getBus()
      ->getBot()
      ->getUserHq();

    // Load user's permissions and notifications
    /** @var \Kaula\TelegramBundle\Entity\User $user */
    $user = $hq->getCurrentUser();
    if (is_null($user)) {
      return;
    }
    $user_permissions = $hq->getUserPermissions();
    $user_notifications = $hq->getUserNotifications();

    // Load notification
    /** @var \Kaula\TelegramBundle\Entity\Notification $notification */
    $notification = $this->getBus()
      ->getBot()
      ->getContainer()
      ->get('doctrine')
      ->getRepository('KaulaTelegramBundle:Notification')
      ->findOneBy(['name' => $cq->data]);
    if (is_null($notification)) {
      return;
    }
    // Check user permission
    if (!$user_permissions->contains($notification->getPermission())) {
      return;
    }

    // Enable notification or disable it
    if ($user_notifications->contains($notification)) {
      $user->removeNotification($notification);
    } else {
      $user->addNotification($notification);
    }
    // Save changes
    $em = $this->getBus()
      ->getBot()
      ->getContainer()
      ->get('doctrine')
      ->getManager();
    $em->persist($user);
    $em->flush();

    // Update keyboard
    $this->getBus()
      ->getBot()
      ->editMessageReplyMarkup(
        $cq->message->chat->id,
        $cq->message->message_id,
        null,
        $this->getReplyKeyboardMarkup_Notifications()
      );
    // Answer callback
    $this->getBus()
      ->getBot()
      ->answerCallbackQuery($cq->id, 'Настройки уведомлений изменены.');
    // Register this hook again
    $this->getBus()
      ->getHooker()
      ->createHook(
        $this->getUpdate(),
        get_class($this),
        'handleSettingsNotificationOption'
      );
  }

}