<div align="center">

# HelpSphere

🏆 **Third place at Innovare** · Innovatia'26 · IEM Kolkata

*Sharing surplus, restoring dignity.*

</div>

---

Every evening, good food is thrown away after weddings, in hostel kitchens and at restaurant closing time.
A few streets away, a family is wondering what they will eat tomorrow. The food and the need are rarely far apart.
What's missing is a quick, trustworthy way to bring them together, and that is what HelpSphere tries to be.

## How it works

Picture a caterer with 40 plates left over after an event. They post an immediate donation, saying what the food is,
how much there is and where to collect it. The five nearest verified NGOs get an email with an *Accept* link, and the
first one to click it takes the donation. If nobody answers within seven minutes, the next five NGOs are alerted, and
so on until someone says yes.

Not everything is that urgent. Donors can also schedule a pickup, answer a food request an NGO has posted, offer
clothes, books or supplies, or volunteer for an NGO's campaign. Money goes straight from the donor to a verified NGO.
HelpSphere never handles it.

Asking for help should be just as easy. A person in need creates an account, describes what they need and uploads
their documents once. An NGO checks the documents and approves the request, and from then on the person can follow
each step on a timeline and message the NGO along the way.

All of this depends on trust, so admins add every NGO and review its registration and compliance documents before it
receives any food alerts. NGOs check beneficiary documents and can report anything that looks wrong. Every donation is
tracked from donated to accepted to picked up, so everyone sees the same picture.

## Running it on XAMPP

HelpSphere is plain PHP and MySQL with no framework and no build step, so [XAMPP](https://www.apachefriends.org)
(PHP 8.1 or newer) is all you need.

1. Clone the project into `C:\xampp\htdocs` as a folder named `HelpSphere`:
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/soham-newjourney2/1.HelpSphere.git HelpSphere
   ```
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. In [phpMyAdmin](http://localhost/phpmyadmin), create a database called `helpsphere` (collation `utf8mb4_general_ci`)
   and import `database/helpsphere.sql` into it.
4. Copy `inc/config.local.example.php` to `inc/config.local.php`. It already matches a fresh XAMPP install, so you only
   need to edit it if you set a MySQL password or used a different folder name. Git ignores this file.
5. Create the first admin from phpMyAdmin's **SQL** tab:
   ```sql
   INSERT INTO admin_master (name, email, password)
   VALUES ('Admin', 'admin@example.com', 'ChangeThisPassword');
   ```
   The plain-text password is replaced with a secure hash the first time you sign in.
6. Open [http://localhost/HelpSphere/](http://localhost/HelpSphere/), sign in as admin and add your first NGO.
   Donors and people in need sign up on their own.

### Food alerts

The site works without this part, but the seven-minute hand-off needs two more pieces. First, fill in the `SMTP_…`
settings and `APP_URL` in `inc/config.local.php` (for Gmail, use an [App Password](https://myaccount.google.com/apppasswords)).
Then double-click `scripts\start_escalation.bat` and leave the window open. Every 30 seconds it passes any donation
nobody has accepted on to the next five NGOs.

### Upgrading an older database

If your database was created from the old `helpsphere (5).sql`, run this once so that sign-ups, requests and donations
save properly:

```sql
ALTER TABLE assistance_requests MODIFY ngo_id int(11) NOT NULL DEFAULT 0;
ALTER TABLE donations MODIFY target_id int(11) NOT NULL DEFAULT 0;
ALTER TABLE food_donation_requests
  MODIFY status enum('Pending','Donated','Accepted','Picked Up') NOT NULL DEFAULT 'Pending',
  MODIFY donor_id int(11) NOT NULL DEFAULT 0,
  MODIFY quantity varchar(200) NOT NULL DEFAULT '',
  MODIFY food_type enum('Cooked','Packaged','Raw') DEFAULT NULL;
```

### If something goes wrong

| What you see | What to do |
|---|---|
| `DB Connection Failed` | Make sure MySQL is running and the settings in `inc/config.local.php` are correct. |
| **Invalid request.** after a form | Your session expired. Reload the page and try again. |
| A document won't upload | Use a PDF, JPG or PNG of 5 MB or less (NGOs can also upload DOC/DOCX). |
| Apache won't start | Port 80 is taken. Change `Listen 80` to `Listen 8080` in `httpd.conf`, then use `http://localhost:8080/HelpSphere/`. |

## Under the hood

```text
index.php, login-*.php, logout.php   Pages people open directly
inc/        Settings, database, login checks, email and escalation
public/     Beneficiary, donor, NGO and admin pages, plus CSS, JS and icons
database/   helpsphere.sql, the whole schema in one file
scripts/    The escalation loop for immediate donations
```

People trust us with personal details, so passwords are hashed with Argon2id, every form is protected against forged
requests, all output is escaped and every query uses prepared statements. Uploaded files are checked by their contents
and only shown to people allowed to see them. XAMPP is meant for development, so please move to a properly secured
server before going live. For a tour of every page and table, see [docs/structure.md](docs/structure.md).

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

If HelpSphere sparks an idea, or you spot a bug, open an issue or a pull request. We'd love to hear from you.
