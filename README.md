# DiscordWebhooks

В Discord заводите входящий вебхук на нужный канал (или несколько), вставляете URL в админку Flute и отмечаете, какие события с сайта туда слать. Один канал Discord может получать только «регистрации и донаты», другой — весь поток включая новости, если такие события подключены.

Из коробки доступны типы вроде: новый пользователь, вход, подтверждённый аккаунт, привязка соцсети, успешный платёж и неуспешный. Другие модули (тот же **News**) при установке добавляют свои пункты в список — тогда в Discord уходят и публикации, лайки, комментарии, если вы их включили для этого вебхука.

Сообщения в Discord оформляются карточкой: имя сайта, при необходимости пользователь со ссылкой на профиль, сумма и промокод для платежей. Можно задать имя и аватарку «бота» для вебхука и цвет полосы. В админке есть тестовая отправка и счётчики: сколько раз ушло успешно и сколько раз нет.

Логотип сайта в превью попадает только если это растровая картинка (PNG, JPEG и т.п.): Discord в таких карточках SVG не рисует.

Отдельные модули для работы **DiscordWebhooks** не нужны: достаточно Flute и самого пакета. Вебхук должен быть настоящим адресом Discord — система отсекает чужие URL, чтобы никто не подставил ссылку на ваш внутренний сервер.

---

Create a webhook in your Discord channel (or several), paste each URL into Flute’s admin UI, and pick which site events should go there. One channel can get only signups and payments; another can receive the full feed including news-style events when those modules register them.

Built-in events include new user, login, verified account, linked social account, successful payment, and failed payment. Installed modules such as **News** add their own events—article publish, likes, comments—when you subscribe the webhook to them.

Discord messages are rich embeds with site branding, optional user profile links, and payment fields for amounts and promo codes. You can set a per-webhook bot name and avatar plus embed accent color. The admin screen includes a test send and counters for successes and failures.

Site logos only appear in the thumbnail when they are raster images (PNG/JPEG/WebP); SVG does not render in Discord embeds.

No other Flute modules are required to use **DiscordWebhooks**—only the module itself. Webhook URLs must be real Discord endpoints; non-Discord URLs are rejected for safety.

## Installation

Download the latest release and install it via the Flute CMS admin panel.

Current version: **1.0.0**

## Authors

- Flames

## Links

- [Flute CMS](https://flute-cms.com)
- [Module page](https://flute-cms.com/market/discordwebhooks)
