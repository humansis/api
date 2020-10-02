#!/usr/bin/env bash

# parameters:
# $1: environment (dev, test, stage, demo, prod)
# $2: clean database (true, false)
# $3: load fixtures (dev, test, false)
# $4: cache clear mode (normal, aggressive)

# immediately exits if a command exits with an non-zero status
set -e

# configure
echo "Configuring application build"
if [[ $1 == "prod" ]]; then
  ec2_host="api.humansis.org"
  mv docker/docker-compose.yml.prod docker-compose.yml
  bash apply_env_config.sh ${RDS_HOSTNAME_PROD} ${RDS_DB_NAME_PROD} ${RDS_USERNAME_PROD} ${RDS_PASSWORD_PROD}
elif [[ $1 == "demo" ]]; then
  ec2_host="api-demo.humansis.org"
  mv docker/docker-compose.yml.demo docker-compose.yml
  bash apply_env_config.sh ${RDS_HOSTNAME_DEMO} ${RDS_DB_NAME_DEMO} ${RDS_USERNAME_DEMO} ${RDS_PASSWORD_DEMO}
elif [[ $1 == "stage" ]]; then
  ec2_host="apistage.humansis.org"
  mv docker/docker-compose.yml.stage docker-compose.yml
  bash apply_env_config.sh ${RDS_HOSTNAME_STAGE} ${RDS_DB_NAME_STAGE} ${RDS_USERNAME_STAGE} ${RDS_PASSWORD_STAGE}
elif [[ $1 == "test" ]]; then
  ec2_host="apitest.humansis.org"
  mv docker/docker-compose.yml.test docker-compose.yml
  bash apply_env_config.sh ${RDS_HOSTNAME_TEST} ${RDS_DB_NAME_TEST} ${RDS_USERNAME_TEST} ${RDS_PASSWORD_TEST}
elif [[ $1 == "dev" ]]; then
  ec2_host="apidev.humansis.org"
  mv docker/docker-compose.yml.dev docker-compose.yml
  bash apply_env_config.sh ${RDS_HOSTNAME_DEV} ${RDS_DB_NAME_DEV} ${RDS_USERNAME_DEV} ${RDS_PASSWORD_DEV}
else
  echo "Wrong environment parameter. Options are: [dev, test, stage, demo, prod]"
  exit 1
fi
echo "...done"

# add host to known_hosts
if [ -z `ssh-keygen -F $ec2_host` ]; then
  ssh-keyscan -H $ec2_host >> ~/.ssh/known_hosts
fi

# get app version
echo "Getting application information"
bash get_info.sh
echo "...done"

# deploy files to host
echo "Upload application files to remote server"
rsync --progress -avz -e "ssh -i ec2_bms.pem" --exclude-from='sync_excludes' ./* ubuntu@$ec2_host:/var/www/html/bms_api/ --delete
echo "...done"
echo "Starting application containers"
start_app="cd /var/www/html/bms_api && sudo docker-compose up -d"
ssh -i ec2_bms.pem ubuntu@$ec2_host $start_app
echo "...done"
echo "Loading composer files"
load_composer="cd /var/www/html/bms_api && sudo docker-compose exec -T php bash -c 'composer install'"
ssh -i ec2_bms.pem ubuntu@$ec2_host $load_composer
echo "...done"

# clean database
echo "Cleaning database"
if [[ $2 == "true" ]]; then
  clean_database="cd /var/www/html/bms_api && sudo docker-compose exec -T php bash -c 'bash clean_database.sh'"
  ssh -i ec2_bms.pem ubuntu@$ec2_host $clean_database
elif [[ $2 != "false" ]]; then
  echo "Wrong clean database parameter. Options are: [true, false]"
  exit 1
fi
echo "...done"

# load fixtures
echo "Loading fixtures"
if [[ $3 != "false" ]]; then
  if [[ $3 == "dev" ]]; then
    load_fixtures="cd /var/www/html/bms_api && sudo docker-compose exec -T php bash -c 'php bin/console doctrine:fixtures:load --env=dev'"
  elif [[ $3 == "test" ]]; then
    load_fixtures="cd /var/www/html/bms_api && sudo docker-compose exec -T php bash -c 'php bin/console doctrine:fixtures:load --env=test'"
  else
    echo "Wrong fixtures environment parameter. Options are: [dev, test, false (do not load fixtures)]"
    exit 1
  fi
  ssh -i ec2_bms.pem ubuntu@$ec2_host $load_fixtures
fi
echo "...done"

# clear cache
# normal: php bin/console cache:clear --env=prod + php bin/console cache:clear
# aggressive: normal + rm ./var/cache/* + docker restart php_container
echo "Clearing cache"
cache_clear="bash /var/www/html/bms_api/clear_cache.sh $4"
ssh -i ec2_bms.pem ubuntu@$ec2_host $cache_clear
echo "...done"
