![Pulpo Loco](http://pulpolo.co/pulpo.svg)

## Pulpo Loco

A self hosted URL shortener


#### Deploying on Heroku

[![Deploy to Heroku](https://www.herokucdn.com/deploy/button.svg)](https://heroku.com/deploy)


#### Deploying on your own environment

You will need MySQL/PostgreSQL and Apache/Nginx installed.

1. Edit your **src/config.db.ini** file with your user, host, and database.
1. Open either **install/mysql.sql** or **install/pgsql.sql** and load it into your database.
1. If using Apache, the **web/.htaccess** file should handle what you need.
1. If using Nginx, you should use the **src/nginx.conf** file.


See [Tipsy Documentation](https://github.com/tipsyphp/tipsy/wiki) for more information.
