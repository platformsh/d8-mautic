* Clone D8 template, remove .git.
* Move everything but .platform into subdir, `drupal`.
* Clone Mautic template to `mautic`, remove .git.
* Change Mautic app name to "mautic". (Changing Drupal is optional.)
* Merge the routes.yaml files manually; www. for Drupal and mautic. for Mautic.
* Setup 2 DBs in services.yaml (drupal and mautic)
* Update relationships to point to new DB endpoints
* Move queuerabbit to main services.yaml.  Remove mautic/.platform
* `git init && git add .`.
* Reduce both app.yaml's to 1 GB of storage so they fit in a default plan.

* Create blank Psh project. (Dev for now, will be Med or higher later.)
* Use set-remove command from wizard to hook up, then push.
* Both apps will deploy.


Note to self: Need to modify Mautic .gitignore to not ignore the translations directory.  WTF?  Why is it ignoring a directory you need to even run???
