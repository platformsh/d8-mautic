## Goal

To set up a multi-app site with both Drupal 8 and Mautic.  The process is also very similar for any other site that combines two templates.

## Preparation

You will need:

* A new project with no code.  You will need at least a Medium plan to go live, but you can start with a Dev plan for setup.  You will need a Medium plan and a domain configured before the Drupal Mautic module can be configured, however.
* A local working Git client and Composer.


## Problems

Drupal needs a Mautic site URL in order to connect to it.  It will do so over HTTP, not through a backend connection.  That means the domain name will be different on every branch.  Generally you would only care about Mautic tracking on production anyway, so that is not a major issue.

## Steps

### 1. Download the Drupal 8 template

The Platform.sh Drupal 8 template is available on [GitHub](https://github.com/platformsh-templates/drupal8).  Clone it to your local computer, then remove the `.git` directory to reset the repository.

```bash
# Download Drupal 8
git clone https://github.com/platformsh-templates/drupal8.git d8-mautic
# Remove the git repository itself.
cd d8-mautic
rm -rf .git
```

Resetting the repository is technically optional, but the existing history is useless for you and as you will be moving a lot of files around it's easier to not deal with Git at this point.

### 2. Move Drupal to a subdirectory

Make a new directory named `drupal` and move all of the existing files into it, *except* for the `.platform` subdirectory.  Make sure to include the various other dot files.

```bash
mkdir drupal
mv * drupal
mv .platform.app.yaml .env* .editorconfig .gitattributes .gitignore drupal
```

### 3. Add Mautic

Download the [Mautic template](https://github.com/platformsh-templates/mautic) into the project directory and remove its `.git` repository as well.

```bash
# Download Mautic
$ git clone https://github.com/platformsh-templates/mautic.git
# Remove the git repository itself.
$ cd mautic
$ rm -rf .git
```

### 4. Rename applications

The two applications will need unique names, such as `drupal` and `mautic`.  (You may use other names if you wish.)  Change the `name` field in each application's `.platform.app.yaml` file:

```yaml
# in drupal/.platform.app.yaml
name: drupal
```

```yaml
# in mautic/.platform.app.yaml
name: mautic
```

Note: If you have an existing Drupal site you are modifying to include Mautic, *do not rename that application*.  Doing so will result in data loss.

### 5. Update `routes.yaml`

Update the `.platform/routes.yaml` file to supply different routes for each application.  The exact configuration will vary depending on your preferred domains.  For example, the following configuration will serve Drupal from `www.YOURSITE.com` and Mautic from `mautic.YOURSITE.com`:

```yaml
"https://www.{default}/":
    type: upstream
    upstream: "drupal:http"
    cache:
      enabled: true
      cookies: ['/^SS?ESS/', '/^Drupal.visitor/']

"https://{default}/":
    type: redirect
    to: "https://www.{default}/"

"https://mautic.{default}/":
  type: upstream
  upstream: "mautic:http"
  cache:
    enabled: true
```

### 6. Merge the `services.yaml` files

At this point, Drupal's original `services.yaml` is in `.platform/services.yaml` and Mautic's is in `mautic/.platform/services.yaml`.  The second will not be used so its content should be merged into the main one, and then it can be removed.

By default, Mautic and Drupal both use MariaDB; Drupal also uses Redis, and Mautic also uses RabbitMQ.  If you wish to add additional services you may do so.  The same MariaDB service can hold the database for both applications.  A typical configuration for running both applications would looks like this:

```yaml
db:
    type: mariadb:10.4
    disk: 2048
    configuration:
        schemas:
            - drupal
            - mautic
        endpoints:
            drupal:
                default_schema: drupal
                privileges:
                    drupal: admin
            mautic:
                default_schema: mautic
                privileges:
                    mautic: admin

cache:
    type: redis:5.0

queuerabbit:
    type: rabbitmq:3.7
    disk: 256
```

That includes the `cache` service from Drupal, the `queuerabbit` service from Mautic, and a single MariaDB 10.4 server to serve both databases.  It defines two databases, `drupal` and `mautic`, and then creates two separate endpoints of the same name that have full access to their respective databases only.

Note: If you are adding Mautic to an existing Drupal site, you *must* name the Drupal database `main` and the Drupal endpoint `mysql`.  Doing otherwise will result in data loss.

Once that is done, remove the now-unused Mautic `.platform` directory.

```bash
rm -rf mautic/.platform
```

### 7. Update each application's relationships definition.

In `drupal/.platform.app.yaml`, change the `database` relationship to point to the `drupal` endpoint:

```yaml
# in drupal/.platform.app.yaml
relationships:
    database: 'db:drupal'
## Uncomment this line to enable Redis caching for Drupal.
#    redis: 'cache:redis'
```

```yaml
# in mautic/.platform.app.yaml
relationships:
    database: "db:mautic"
    rabbitmqqueue: "queuerabbit:rabbitmq"
```

### 8. Reduce disk usage OR increase plan size

The default templates for Drupal and Mautic, when combined, will ask for a total of about 6 GB of storage.  By default plans on Platform.sh start with 5 GB.  You may either increase the disk usage of your plan to a value higher than 6 GB, OR you can reduce the disk space requested by each application container.

For the latter, change the `disk` key in both `.platform.app.yaml` files to `1024`:

```yaml
disk: 1024
```

That will give each application 1 GB of disk space.  Both applications will share the disk space used for the database (2 GB in the example above).

### 9. Add the Drupal Mautic module to the project

Install the `drupal/mautic` module in the Drupal instance, using Composer:

```bash
cd drupal
composer require drupal/mautic
```

Because of the way Composer works that will require downloading all dependencies, even though they will not be needed.  That's fine.  When it is done go back to the project directory:

```bash
cd ..
```

### 10. Commit and deploy

Initialize a new Git repository and commit all of the files you've created to it.

```bash
git init
git add .
git commit -m "Add Drupal and Mautic."
```

Next, set a Git remote for the empty Platform.sh project you have waiting for it, using the Platform.sh CLI.  You can find the exact command to copy and paste in the project's setup wizard.  Then push your code to the new remote.

```bash
platform project:set-remote YOUR_PROJECT_ID_HERE
git push -u platform master
```

The codebase will push to Platform.sh, and both applications will be built and deployed.

### 11. Install both applications

Once the deploy is complete, run through the web installer for both applications.  The environment URL for each one will be visible in the CLI output as well as in the web console.

Consult the `README.md` file for each application for steps that should be taken post-install.  They are not required for completing the integration but doing so will lead to a better experience for both applications.  You may also do any additional configuration desired for both applications either now or afterward.

### 12. Enable and configure the Drupal module

Once logged into Drupal as the site administrator, go to `/admin/modules` and enable the Mautic module.

Once the module is enabled, go to the module's configuration page at `/admin/config/system/mautic`.  Check "Include Mautic Javascript Code".

For the Mautic URL, enter the domain name of your Mautic site followed by `/mtc.js`.  If you are on a development plan, it will be something similar to:

`https://mautic.master-t6dnbai-aqatptktdkxmi.us-2.platformsh.site/mtc.js`

If you already have a domain name configured, it will be whatever the domain name is that Mautic is served from instead.

Click "save configuration".

The setup is now complete.  Browse to the Drupal home page and inspect the source code to locate the Mautic tracking Javascript code.

## Conclusion

In this tutorial you have learned how to create a multi-application project on Platform.sh based on two separate application templates.  You've also seen how to add a module to Drupal and configure it with Mautic.

The same basic process applies to any other two-template project, although the specific names and services will of course vary depending on the applications.
