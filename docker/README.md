# Docker Compose build and run

```
# create a subnet for ABCD
docker network create --subnet 172.18.18.0/24 abcd
 
# ensure www-data user is owner of the example database
chown -R 33:33 ./www/bases-examples_Linux

# start containers
docker-compose --project-directory . -f docker/docker-compose.yml up
```

# Start containers from dockerhub

Download the image with:
```
docker pull edsz14/abcd-isis
```
 
This command will start a container in the foreground (for testing/debugging, on port 8080):
```
docker run --rm -p 127.0.0.1:8080:80 --name abcd -v <path/to/bases/directory>:/var/opt/ABCD/bases edsz14/abcd-isis
```
 
This command will start a container in the background and ensure it is started automatically whenever docker is started (port 80, requires root):
```
# ensure the www-data user inside the container can read/write your bases
chown -R 33:33 <path/to/bases/directory>

# start the container on port 80
docker run --rm -d --restart always -p 127.0.0.1:80:80 --name abcd -v <path/to/bases/directory>:/var/opt/ABCD/bases edsz14/abcd-isis
```
 
# Reset data (optional)

Wipe logs and mysql data (for docker compose):
```
rm -rf docker/logs/* docker/mysql/data/*
```
