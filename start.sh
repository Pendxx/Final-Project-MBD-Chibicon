#!/bin/bash

# start.sh - Script untuk menjalankan Chibicon Admin Dashboard via Docker

# Warna untuk output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}==================================================${NC}"
echo -e "${BLUE}   Chibicon Admin Dashboard - Docker Starter     ${NC}"
echo -e "${BLUE}==================================================${NC}"

# 1. Cek Docker
if ! command -v docker &> /dev/null
then
    echo -e "${RED}Error: Docker tidak ditemukan.${NC} Silakan install Docker untuk menjalankan aplikasi ini."
    exit 1
fi

# 2. Setup Network
echo -e "\n${GREEN}[1/5] Menyiapkan Jaringan Docker...${NC}"
docker network create chibicon-net 2>/dev/null || true

# 3. Jalankan Database
echo -e "\n${GREEN}[2/5] Memulai Database (MySQL 8.0)...${NC}"
if [ "$(docker ps -aq -f name=chibicon-db)" ]; then
    echo -e "Kontainer database sudah ada, menyalakan ulang..."
    docker start chibicon-db
else
    docker run --name chibicon-db --network chibicon-net -e MYSQL_ALLOW_EMPTY_PASSWORD=yes -p 3307:3306 -d mysql:8.0
fi

# 4. Jalankan Web Server
echo -e "\n${GREEN}[3/5] Memulai Web Server (Apache/PHP)...${NC}"
if [ "$(docker ps -aq -f name=chibicon-web)" ]; then
    echo -e "Kontainer web sudah ada, menyalakan ulang..."
    docker start chibicon-web
else
    docker run --name chibicon-web --network chibicon-net -p 8181:80 -v "$(pwd)":/var/www/html -d php:8.2-apache
    
    echo -e "${YELLOW}Menginstall dependensi PHP (PDO MySQL)...${NC}"
    docker exec chibicon-web docker-php-ext-install pdo pdo_mysql
    docker exec chibicon-web apachectl restart
fi

# 5. Sinkronisasi Database
echo -e "\n${GREEN}[4/5] Sinkronisasi Schema & Triggers...${NC}"
echo -e "Menunggu database siap (15 detik)..."
sleep 15

docker exec chibicon-db mysql -u root -e "CREATE DATABASE IF NOT EXISTS chibicon_db;" 2>/dev/null
docker cp database/chibicon_db.sql chibicon-db:/tmp/
docker cp database/sql/triggers.sql chibicon-db:/tmp/

echo -e "Mengimport tabel..."
docker exec chibicon-db sh -c "mysql -u root chibicon_db < /tmp/chibicon_db.sql"
echo -e "Mengimport triggers..."
docker exec chibicon-db sh -c "mysql -u root chibicon_db < /tmp/triggers.sql" 2>/dev/null || echo -e "${YELLOW}Catatan: Triggers mungkin sudah ada.${NC}"

# 6. Selesai
echo -e "\n${BLUE}==================================================${NC}"
echo -e "${GREEN}   APLIKASI SIAP DI AKSES!${NC}"
echo -e "   URL      : ${BLUE}http://localhost:8181${NC}"
echo -e "   Username : admin"
echo -e "   Password : chibicon2024"
echo -e "${BLUE}==================================================${NC}"
echo -e "Gunakan 'docker stop chibicon-web chibicon-db' untuk mematikan."
