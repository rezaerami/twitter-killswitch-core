# Twitter Killswitch

Twitter Killswitch bootstrapped with [Lravel](https://github.com/laravel/laravel).

Twitter killswitch is a security application that allows you to remove all your tweets with a press of a button.\
in case of emergency you can use this app to avoid any abuse of your twitter account.

## Setup

### Install dependencies

first you need to install dependencies

```$xslt
composer install
```

**make sure to install composer first, here you can [download Composer](https://getcomposer.org/download/)**

### Setting up environment variables

#### Create .env file
then you need to make .env

```$xslt
cp .env.exampe .env
```

now you need to generate a key for your laravel project

#### App Key
```$xslt
php artisan key:generate
```
it will put a key in front of ``APP_KEY``

#### Configure URIs
there are two URL that api needs, one for API, and one for UI for redirects,

lets say your domain is called ``foo.com``, your API will be deployed on ``api.foo.com`` and your UI will be deployed on ``ui.foo.com``.\
so you need to configure the variables like this

```$xslt
# OTHER VARIABLES...

APP_URL=https://api.foo.com

# OTHER VARIABLES...

FRONTEND_URL="https://ui.foo.com"

# OTHER VARIABLES...
```

**NOTE: DO NOT PUT SLASH IN THE END**

#### Configure sessions
in case you want to set up the UI and API on different subdomains you need to change the `SESSION_DOMAIN` variable.
lets say you'll deploy apu on ``api.foo.com`` and ui on ``ui.foo.com``, to share the cookies you need to set ``SESSION_DOMAIN`` variable to ``.foo.com``\
e.g.

```$xslt
SESSION_NAME=".foo.com"
```

#### Configure Database
To connect to database you need to set these variables, by default mysql and mariadb will run on ``3306`` unless you change them

make sure to set ``DB_DATABASE``, ``DB_USERNAME``, ``DB_PASSWORD`` with the correct info

```$xslt
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=
```

#### Configure queue
To set up the queue you need to change ``QUEUE_CONNECTION`` variable.\
in case you want to run your jobs on background it is required to change it from ``sync`` to something else, otherwise you will get timeout cause it will handle everything in foreground.\
you can choose between ``database`` or ``redis``.\
e.g.
```$xslt
QUEUE_CONNECTION=database
```

**NOTE: in case you want to use redis, make sure to [install redis](https://redis.io/) first.**


#### Twitter configuration
For Twitter integration we use [atymic/twitter](https://github.com/atymic/twitter) library.
you're going to need four different keys that Twitter provides you when you create an app in your twitter developer account.\
you can use [this tutorial](https://www.cozmoslabs.com/docs/profile-builder-2/add-ons/social-connect/create-twitter-app-social-connect/) to create a project and app.

**NOTE: MAKE SURE TO REQUEST FOR WRITE ACCESS, SINCE KILLSWITCH WANTS TO DELETE TWEETS**

```$xslt
TWITTER_CONSUMER_KEY=
TWITTER_CONSUMER_SECRET=
TWITTER_ACCESS_TOKEN=
TWITTER_ACCESS_TOKEN_SECRET=
```

you can get consumer keys and access token and secret, from keys and tokens tab on your application page in [Twitter developer portal](https://developer.twitter.com/en/portal/dashboard)

#### Hash id configuration
[Hashids](https://github.com/vinkla/hashids) is a small PHP library to generate YouTube-like ids from numbers. Use it when you don't want to expose your database numeric ids to users

to hash your ids you need to first generate a salt using openssl rand method, and set it in ``.env`` file\
to generate a salt with openssl first run this command in your terminal
```$xslt
openssl rand -hex 32
```
then copy the output and paste it in front of ``HASHIDS_SALT`` variable.

### Setting up the project

#### Database
first you need to migrate your tables, in order to do the migration you need to execute this command
```$xslt
php artisan migrate
```
you can find full docs about laravel migrations [here](https://laravel.com/docs/9.x/migrations).

#### Queue
if you choose ``database`` or ``redis`` as ``QUEUE_CONNECTION`` you need to enable queue worker

**Run queue on foreground**
```$xslt
php artisan queue:work
```
**Run queue on background**
```$xslt
nohup php path/tp/project/root/artisan queue:work --daemon > /dev/null 2>&1 &
```
**NOTE: you can monitor jobs in ``jobs`` and ``failed_jobs`` tables.\
user tokens in jobs will remain encrypted**

you can find full docs about laravel queue [here](https://laravel.com/docs/9.x/queues).

#### Scheduling
Scheduling allows you to run task with a period.\
**we schedule to remove users that haven't used platform in eight days.**

**Run schedule on foreground**
```$xslt
php artisan schedule:run
```
**Run schedule on background**
you need to add schedule to crontabs.

first run bellow command to open crontab editor.
```$xslt
crontab -e
```

then add this line to crontab

```$xslt
* * * * * php path/tp/project/root/artisan schedule:run >> /dev/null 2>&1
```
this will add schedule command to crontab and run it every second.

**NOTE: actual scheduling will happen in the ``app\Console\Kernel.php`` file which registeres a daily task for deleting the users.**
you can find full docs about laravel scheduling [here](https://laravel.com/docs/9.x/scheduling).

**NOTE: MAKE SURE TO HAVE ``crontabs`` installed on your server**
```$xslt
yum install cronie
```

### Running project
on local you can use dev server with command bellow

```$xslt
php artisan serve
```
