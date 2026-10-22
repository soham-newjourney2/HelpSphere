<div align="center">

# HelpSphere

**Sharing surplus, restoring dignity.**

</div>

Every evening, good food gets thrown away — after weddings, in hostel kitchens, at the end of a busy day in a restaurant.
A few streets away, a family is wondering what they will eat tomorrow. The food and the need are often close to each other;
what is missing is a quick, trustworthy way to connect them.

HelpSphere is our attempt to build that connection. We started it as a hackathon project at **Innovatia'26, IEM Kolkata**,
where it won **3rd place in Innovare** — and we have kept improving it since.

## A donation's journey

Picture a caterer with 40 plates of food left over after an event.

They open HelpSphere and post an **immediate donation**: what the food is, roughly how much, and where it can be picked up.
Within moments, the **five nearest verified NGOs** get an email with an *Accept* link.
The first NGO to click it gets the donation, and the caterer can see who is coming.
If nobody responds within **seven minutes**, HelpSphere quietly moves on to the next five NGOs — and keeps going until someone says yes.

Not every donation is that urgent. A donor can also **schedule a pickup** for a day and time that suits them,
answer a **food request an NGO has posted**, offer **clothes, books or supplies**, or **sign up to volunteer** for an NGO's campaign.
When it comes to money, HelpSphere stays out of the way on purpose: donors find a verified NGO and give to it directly.
We never touch a rupee.

## The other side of the story

Help also has to be easy to ask for.

Someone going through a hard time creates an account, describes what they need — food or other essentials —
and uploads their documents **once**. An NGO reviews the request, checks the documents and approves it.
From then on, the person can follow every step on a simple timeline, right up to the moment their request is fulfilled,
and they can message the NGO along the way.

## Keeping it trustworthy

None of this works unless people can trust who is on the other end.

- **NGOs are verified before they get alerts.** An admin adds each NGO, the NGO uploads its registration and compliance documents,
  and an admin reviews every one. Only fully verified NGOs receive immediate food donations.
- **Beneficiary documents are checked** by NGOs, who can also report anything that looks wrong.
- **Every step is recorded** — donated, accepted, picked up — so donors and NGOs always see the same picture.

## Who uses it

| | |
|---|---|
| **Donors** | Give food now or later, answer NGO requests, offer resources, find NGOs to support, volunteer, and message NGOs |
| **People in need** | Ask for help, upload documents once, follow each request, and talk to NGOs |
| **NGOs** | Accept donations and mark pickups, review and fulfil requests, post their own needs, run campaigns with volunteers |
| **Admins** | Add and verify NGOs, and look after every account |

## Try it yourself

HelpSphere is plain PHP and MySQL — no framework and no build step — so all you need is **XAMPP**.

**1. Install XAMPP** with PHP 8.1 or newer from [apachefriends.org](https://www.apachefriends.org).
The default location, `C:\xampp`, is fine.

**2. Put the project inside `htdocs`**, in a folder called `HelpSphere`:

```bash
cd C:\xampp\htdocs
git clone https://github.com/soham-newjourney2/1.HelpSphere.git HelpSphere
```

(Or download the ZIP from GitHub, extract it there, and rename the folder to `HelpSphere`.)

**3. Start Apache and MySQL** from the XAMPP Control Panel.

**4. Create the database.** Open [phpMyAdmin](http://localhost/phpmyadmin), click **New**, name the database `helpsphere`
(collation `utf8mb4_general_ci`) and click **Create**. Then, with `helpsphere` selected, use the **Import** tab
to load `database/helpsphere.sql` — the whole database structure lives in that one file.

**5. Add your settings.** Copy `inc/config.local.example.php` to `inc/config.local.php`.
It already matches a fresh XAMPP install (user `root`, no password). Only change it if you set a MySQL password,
picked a different database name, or used a folder other than `HelpSphere` (then update `APP_URL`).
This file is ignored by git, so your passwords stay on your machine.

**6. Create the first admin.** In phpMyAdmin, open the **SQL** tab of the `helpsphere` database and run this with your own details:

```sql
INSERT INTO admin_master (name, email, password)
VALUES ('Admin', 'admin@example.com', 'ChangeThisPassword');
```

Don't worry about the plain-text password — it is replaced with a secure hash the first time you sign in.

**7. Open [http://localhost/HelpSphere/](http://localhost/HelpSphere/).**
Sign in as the admin, add an NGO under **Add NGO**, and you are ready to go.
The NGO can then sign in and upload its documents for you to verify, while donors and people in need
create their own accounts from their sign-in pages.

## Turning on the food alerts

The site works without this part, but the seven-minute magic needs two more things.

**Email.** Fill in the `SMTP_…` settings in `inc/config.local.php`, and set `APP_URL` to your site's address so the
*Accept* links point to the right place. If you use Gmail, turn on 2-Step Verification and create an
[App Password](https://myaccount.google.com/apppasswords) for `SMTP_PASS`.

**The escalation loop.** Double-click `scripts\start_escalation.bat` and leave the window open.
Every 30 seconds it checks for donations nobody has accepted and alerts the next five NGOs.
It uses `C:\xampp\php\php.exe`; if XAMPP lives somewhere else, edit the path at the top of that file.
Without it, the first five NGOs are still alerted, but a donation won't be passed on if none of them respond.

## Coming from an older version?

If your database was created from an earlier SQL file (`helpsphere (5).sql`), run this once in phpMyAdmin's **SQL** tab
so sign-ups, requests and donations save properly:

```sql
ALTER TABLE assistance_requests MODIFY ngo_id int(11) NOT NULL DEFAULT 0;
ALTER TABLE donations MODIFY target_id int(11) NOT NULL DEFAULT 0;
ALTER TABLE food_donation_requests
  MODIFY status enum('Pending','Donated','Accepted','Picked Up') NOT NULL DEFAULT 'Pending',
  MODIFY donor_id int(11) NOT NULL DEFAULT 0,
  MODIFY quantity varchar(200) NOT NULL DEFAULT '',
  MODIFY food_type enum('Cooked','Packaged','Raw') DEFAULT NULL;
```

## If something goes wrong

| What you see | What to do |
|---|---|
| `DB Connection Failed` | Check that MySQL is running in XAMPP, and that the database name and password in `inc/config.local.php` are right. |
| **Invalid request.** after submitting a form | Your session timed out. Go back, reload the page and try again. |
| A document won't upload | Use a PDF, JPG or PNG (NGOs can also upload DOC/DOCX) of 5 MB or less. |
| Alert emails never arrive | Recheck the `SMTP_…` settings. PHP writes its errors to `C:\xampp\php\logs\php_error_log`. |
| Apache won't start | Something else is using port 80. In XAMPP choose **Config → httpd.conf**, change `Listen 80` to `Listen 8080`, then use `http://localhost:8080/HelpSphere/` and update `APP_URL`. |

## Finding your way around the code

```text
index.php, login-*.php, logout.php   The pages people open directly
inc/                Shared PHP: settings, database, login checks, email, escalation
public/             Beneficiary, donor, NGO and admin pages, plus CSS, JavaScript and icons
database/           helpsphere.sql — the whole database structure in one file
scripts/            The escalation loop for immediate donations
docs/               Developer documentation
```

Uploaded documents are kept in an `uploads/` folder that is created on the first upload and never stored in git.
For a tour of every page, the database tables and how a page is put together, read **[docs/structure.md](docs/structure.md)**.

## Looking after people's data

People share personal details and documents here, so we try to treat them with care:
passwords are hashed with Argon2id, every form that changes something is protected against forged requests,
everything shown on a page is escaped, and every database query uses prepared statements.
Uploaded files are checked by their contents and only shown to people who are allowed to see them,
and the root `.htaccess` stops Apache from serving the app's internal folders.

XAMPP is meant for development, so please use a properly secured server before putting HelpSphere on the internet.

## The team

<div align="center">
<table>
  <tr>
    <td align="center" width="25%">
      <a href="https://github.com/soham-newjourney2">
        <img src="https://github.com/soham-newjourney2.png?size=200" width="110" alt="Soham Chowdhury">
      </a>
      <h3>Soham Chowdhury</h3>
      <sub><b>Team Lead</b> · Frontend</sub><br><br>
      <a href="https://github.com/soham-newjourney2">@soham-newjourney2</a>
    </td>
    <td align="center" width="25%">
      <a href="https://github.com/Arkoprabho-Roy">
        <img src="https://github.com/Arkoprabho-Roy.png?size=200" width="110" alt="Arkoprabho Roy">
      </a>
      <h3>Arkoprabho Roy</h3>
      <sub><b>Backend</b></sub><br><br>
      <a href="https://github.com/Arkoprabho-Roy">@Arkoprabho-Roy</a>
    </td>
    <td align="center" width="25%">
      <a href="https://github.com/srijoyeemisra">
        <img src="https://github.com/srijoyeemisra.png?size=200" width="110" alt="Srijoyee Misra">
      </a>
      <h3>Srijoyee Misra</h3>
      <sub>Contributor</sub><br><br>
      <a href="https://github.com/srijoyeemisra">@srijoyeemisra</a>
    </td>
    <td align="center" width="25%">
      <a href="https://github.com/blacksayan6">
        <img src="https://github.com/blacksayan6.png?size=200" width="110" alt="Sayan Roy Chowdhury">
      </a>
      <h3>Sayan Roy Chowdhury</h3>
      <sub>Contributor</sub><br><br>
      <a href="https://github.com/blacksayan6">@blacksayan6</a>
    </td>
  </tr>
</table>
</div>

HelpSphere began as a hackathon idea at Innovatia'26, IEM Kolkata. If it sparks something for you —
an idea, a bug report or a pull request — we would love to hear from you.
