#!/usr/bin/env bash

# Billing-Panel Troubleshooting and Health Check Script
# Usage: bash scripts/health-check.sh

set -euo pipefail

COMPOSE_FILE="docker-compose.yml"
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}================================${NC}"
echo -e "${BLUE}Billing-Panel Health Check${NC}"
echo -e "${BLUE}================================${NC}"
echo ""

# Check Docker
echo -e "${BLUE}[1/6] Checking Docker...${NC}"
if command -v docker &> /dev/null; then
    docker_version=$(docker --version)
    echo -e "${GREEN}✅ Docker is installed: ${docker_version}${NC}"
else
    echo -e "${RED}❌ Docker is not installed${NC}"
    exit 1
fi

# Check Docker Compose
echo ""
echo -e "${BLUE}[2/6] Checking Docker Compose...${NC}"
if command -v docker compose &> /dev/null; then
    compose_version=$(docker compose --version)
    echo -e "${GREEN}✅ Docker Compose is installed: ${compose_version}${NC}"
else
    echo -e "${RED}❌ Docker Compose is not installed${NC}"
    exit 1
fi

# Check if docker-compose.yml exists
echo ""
echo -e "${BLUE}[3/6] Checking docker-compose.yml...${NC}"
if [ -f "$COMPOSE_FILE" ]; then
    echo -e "${GREEN}✅ docker-compose.yml found${NC}"
    docker compose config --quiet && echo -e "${GREEN}✅ docker-compose.yml is valid${NC}" || {
        echo -e "${RED}❌ docker-compose.yml has errors${NC}"
        docker compose config
        exit 1
    }
else
    echo -e "${RED}❌ docker-compose.yml not found${NC}"
    exit 1
fi

# Check running containers
echo ""
echo -e "${BLUE}[4/6] Checking running containers...${NC}"
running=$(docker compose ps -q 2>/dev/null | wc -l)
if [ "$running" -gt 0 ]; then
    echo -e "${GREEN}✅ $running containers are running${NC}"
    docker compose ps
else
    echo -e "${YELLOW}⚠️  No containers are running${NC}"
    echo "Start containers with: docker compose up -d"
fi

# Check container health
echo ""
echo -e "${BLUE}[5/6] Checking container health...${NC}"
containers=$(docker compose ps -q 2>/dev/null || echo "")

if [ -z "$containers" ]; then
    echo -e "${YELLOW}⚠️  No containers running${NC}"
else
    # Check each service
    for service in app db redis web worker scheduler; do
        container=$(docker compose ps -q $service 2>/dev/null || echo "")
        if [ -z "$container" ]; then
            continue
        fi
        
        status=$(docker inspect --format='{{.State.Status}}' "$container" 2>/dev/null || echo "unknown")
        health=$(docker inspect --format='{{.State.Health.Status}}' "$container" 2>/dev/null || echo "no health check")
        
        if [ "$status" = "running" ]; then
            if [ "$health" = "healthy" ] || [ "$health" = "no health check" ]; then
                echo -e "${GREEN}✅ $service: $status ($health)${NC}"
            else
                echo -e "${YELLOW}⚠️  $service: $status (health: $health)${NC}"
            fi
        else
            echo -e "${RED}❌ $service: $status${NC}"
        fi
    done
fi

# Check .env file
echo ""
echo -e "${BLUE}[6/6] Checking .env file...${NC}"
if [ -f ".env" ]; then
    echo -e "${GREEN}✅ .env file exists${NC}"
    
    # Check for required variables
    required_vars=("APP_NAME" "APP_URL" "DB_HOST" "DB_DATABASE" "DB_USERNAME" "DB_PASSWORD")
    missing=0
    
    for var in "${required_vars[@]}"; do
        if grep -q "^${var}=" .env 2>/dev/null; then
            value=$(grep "^${var}=" .env | cut -d'=' -f2- || echo "")
            if [ -z "$value" ] && [ "$var" != "APP_KEY" ]; then
                echo -e "${YELLOW}⚠️  $var is empty${NC}"
            fi
        else
            echo -e "${RED}❌ Missing: $var${NC}"
            missing=$((missing+1))
        fi
    done
    
    if [ $missing -eq 0 ]; then
        echo -e "${GREEN}✅ All required variables are set${NC}"
    fi
else
    echo -e "${RED}❌ .env file not found${NC}"
    echo "Copy .env.example: cp .env.example .env"
    exit 1
fi

echo ""
echo -e "${BLUE}================================${NC}"
echo -e "${GREEN}Health check complete!${NC}"
echo -e "${BLUE}================================${NC}"
echo ""

# Provide quick fixes
echo -e "${YELLOW}Quick Fixes:${NC}"
echo "  • Start containers:  docker compose up -d"
echo "  • View logs:         docker compose logs -f app"
echo "  • Restart service:   docker compose restart app"
echo "  • Full rebuild:      docker compose up -d --build"
echo "  • Database shell:    docker compose exec db mysql -u root -p billing"
echo ""
