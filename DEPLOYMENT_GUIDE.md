# AutoERP Deployment Guide

This guide covers production deployment of AutoERP on various cloud platforms and infrastructure setups.

## Table of Contents

1. [Pre-Deployment Checklist](#pre-deployment-checklist)
2. [Cloud Platform Deployment](#cloud-platform-deployment)
   - [AWS Deployment](#aws-deployment)
   - [Azure Deployment](#azure-deployment)
   - [Google Cloud Deployment](#google-cloud-deployment)
3. [Docker Production Deployment](#docker-production-deployment)
4. [Kubernetes Deployment](#kubernetes-deployment)
5. [Database Setup](#database-setup)
6. [Redis Setup](#redis-setup)
7. [File Storage Setup](#file-storage-setup)
8. [SSL/TLS Configuration](#ssltls-configuration)
9. [Environment Configuration](#environment-configuration)
10. [Performance Optimization](#performance-optimization)
11. [Monitoring and Logging](#monitoring-and-logging)
12. [Backup and Recovery](#backup-and-recovery)
13. [Security Hardening](#security-hardening)
14. [Continuous Deployment](#continuous-deployment)

## Pre-Deployment Checklist

Before deploying to production, ensure:

- [ ] All tests pass (`php artisan test`)
- [ ] Code quality checks pass (`./vendor/bin/pint`, `./vendor/bin/phpstan`)
- [ ] Security scan completed (CodeQL, dependency audit)
- [ ] Environment variables configured for production
- [ ] Database backups configured
- [ ] SSL certificates obtained
- [ ] Domain DNS configured
- [ ] Monitoring and alerting set up
- [ ] Error tracking configured (Sentry)
- [ ] Documentation updated
- [ ] Rollback plan prepared

## Cloud Platform Deployment

### AWS Deployment

#### Architecture Overview

```
┌─────────────────────────────────────────────────────────┐
│                    Route 53 (DNS)                       │
└────────────────┬────────────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────────────┐
│              CloudFront (CDN)                           │
└────────────────┬────────────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────────────┐
│        Application Load Balancer (ALB)                  │
└────────┬───────┬────────────────────────────────────────┘
         │       │
    ┌────▼───┐ ┌▼─────┐
    │ ECS/EC2│ │ECS/EC2│  Auto Scaling Group
    └────┬───┘ └┬─────┘
         │      │
    ┌────▼──────▼─────┐
    │  RDS PostgreSQL  │  Multi-AZ
    │  ElastiCache     │  Redis
    │  S3              │  File Storage
    └──────────────────┘
```

#### Step-by-Step AWS Deployment

##### 1. Set Up VPC and Networking

```bash
# Create VPC
aws ec2 create-vpc \
  --cidr-block 10.0.0.0/16 \
  --tag-specifications 'ResourceType=vpc,Tags=[{Key=Name,Value=AutoERP-VPC}]'

# Create subnets (public and private in multiple AZs)
aws ec2 create-subnet \
  --vpc-id vpc-xxxxx \
  --cidr-block 10.0.1.0/24 \
  --availability-zone us-east-1a

aws ec2 create-subnet \
  --vpc-id vpc-xxxxx \
  --cidr-block 10.0.2.0/24 \
  --availability-zone us-east-1b
```

##### 2. Set Up RDS PostgreSQL

```bash
# Create RDS instance
aws rds create-db-instance \
  --db-instance-identifier autoerp-db \
  --db-instance-class db.t3.medium \
  --engine postgres \
  --engine-version 15.3 \
  --master-username admin \
  --master-user-password YourSecurePassword \
  --allocated-storage 100 \
  --storage-type gp3 \
  --multi-az \
  --backup-retention-period 7 \
  --vpc-security-group-ids sg-xxxxx
```

##### 3. Set Up ElastiCache Redis

```bash
# Create Redis cluster
aws elasticache create-cache-cluster \
  --cache-cluster-id autoerp-redis \
  --cache-node-type cache.t3.medium \
  --engine redis \
  --num-cache-nodes 1 \
  --cache-subnet-group-name my-subnet-group \
  --security-group-ids sg-xxxxx
```

##### 4. Set Up S3 for File Storage

```bash
# Create S3 bucket
aws s3 mb s3://autoerp-files

# Configure bucket policy
aws s3api put-bucket-policy \
  --bucket autoerp-files \
  --policy file://s3-policy.json
```

##### 5. Set Up ECS Cluster

```bash
# Create ECS cluster
aws ecs create-cluster --cluster-name autoerp-cluster

# Build and push Docker image to ECR
aws ecr create-repository --repository-name autoerp

# Build and push
docker build -t autoerp .
docker tag autoerp:latest 123456789012.dkr.ecr.us-east-1.amazonaws.com/autoerp:latest
aws ecr get-login-password --region us-east-1 | docker login --username AWS --password-stdin 123456789012.dkr.ecr.us-east-1.amazonaws.com
docker push 123456789012.dkr.ecr.us-east-1.amazonaws.com/autoerp:latest
```

##### 6. Create ECS Task Definition

```json
{
  "family": "autoerp",
  "networkMode": "awsvpc",
  "requiresCompatibilities": ["FARGATE"],
  "cpu": "1024",
  "memory": "2048",
  "containerDefinitions": [
    {
      "name": "app",
      "image": "123456789012.dkr.ecr.us-east-1.amazonaws.com/autoerp:latest",
      "portMappings": [
        {
          "containerPort": 9000,
          "protocol": "tcp"
        }
      ],
      "environment": [
        {"name": "APP_ENV", "value": "production"},
        {"name": "DB_HOST", "value": "autoerp-db.xxxxx.us-east-1.rds.amazonaws.com"},
        {"name": "REDIS_HOST", "value": "autoerp-redis.xxxxx.cache.amazonaws.com"}
      ],
      "secrets": [
        {"name": "APP_KEY", "valueFrom": "arn:aws:secretsmanager:..."},
        {"name": "DB_PASSWORD", "valueFrom": "arn:aws:secretsmanager:..."}
      ],
      "logConfiguration": {
        "logDriver": "awslogs",
        "options": {
          "awslogs-group": "/ecs/autoerp",
          "awslogs-region": "us-east-1",
          "awslogs-stream-prefix": "app"
        }
      }
    }
  ]
}
```

##### 7. Create Application Load Balancer

```bash
# Create ALB
aws elbv2 create-load-balancer \
  --name autoerp-alb \
  --subnets subnet-xxxxx subnet-yyyyy \
  --security-groups sg-xxxxx

# Create target group
aws elbv2 create-target-group \
  --name autoerp-targets \
  --protocol HTTP \
  --port 80 \
  --vpc-id vpc-xxxxx \
  --health-check-path /api/health
```

##### 8. Create ECS Service

```bash
aws ecs create-service \
  --cluster autoerp-cluster \
  --service-name autoerp-service \
  --task-definition autoerp:1 \
  --desired-count 2 \
  --launch-type FARGATE \
  --network-configuration "awsvpcConfiguration={subnets=[subnet-xxxxx,subnet-yyyyy],securityGroups=[sg-xxxxx],assignPublicIp=ENABLED}" \
  --load-balancers "targetGroupArn=arn:aws:elasticloadbalancing:...,containerName=app,containerPort=9000"
```

### Azure Deployment

#### Architecture Overview

```
┌─────────────────────────────────────────────────────────┐
│              Azure Front Door (CDN)                     │
└────────────────┬────────────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────────────┐
│          Application Gateway (Load Balancer)            │
└────────┬───────┬────────────────────────────────────────┘
         │       │
    ┌────▼───┐ ┌▼─────┐
    │App Svc │ │App Svc│  Auto Scale
    └────┬───┘ └┬─────┘
         │      │
    ┌────▼──────▼─────┐
    │  Azure Database  │  PostgreSQL
    │  Azure Cache     │  Redis
    │  Blob Storage    │  Files
    └──────────────────┘
```

#### Step-by-Step Azure Deployment

##### 1. Create Resource Group

```bash
az group create \
  --name autoerp-rg \
  --location eastus
```

##### 2. Create Azure Database for PostgreSQL

```bash
az postgres flexible-server create \
  --resource-group autoerp-rg \
  --name autoerp-db \
  --location eastus \
  --admin-user dbadmin \
  --admin-password YourSecurePassword \
  --sku-name Standard_D2s_v3 \
  --storage-size 128 \
  --version 15
```

##### 3. Create Azure Cache for Redis

```bash
az redis create \
  --resource-group autoerp-rg \
  --name autoerp-cache \
  --location eastus \
  --sku Standard \
  --vm-size c1
```

##### 4. Create Storage Account

```bash
az storage account create \
  --name autoerpstorage \
  --resource-group autoerp-rg \
  --location eastus \
  --sku Standard_LRS
```

##### 5. Create Container Registry

```bash
az acr create \
  --resource-group autoerp-rg \
  --name autoerp \
  --sku Standard
```

##### 6. Build and Push Docker Image

```bash
az acr build \
  --registry autoerp \
  --image autoerp:latest \
  .
```

##### 7. Create App Service Plan

```bash
az appservice plan create \
  --name autoerp-plan \
  --resource-group autoerp-rg \
  --is-linux \
  --sku P1V2
```

##### 8. Create Web App

```bash
az webapp create \
  --resource-group autoerp-rg \
  --plan autoerp-plan \
  --name autoerp \
  --deployment-container-image-name autoerp.azurecr.io/autoerp:latest
```

##### 9. Configure App Settings

```bash
az webapp config appsettings set \
  --resource-group autoerp-rg \
  --name autoerp \
  --settings \
    APP_ENV=production \
    DB_HOST=autoerp-db.postgres.database.azure.com \
    REDIS_HOST=autoerp-cache.redis.cache.windows.net
```

### Google Cloud Deployment

#### Architecture Overview

```
┌─────────────────────────────────────────────────────────┐
│              Cloud CDN + Load Balancer                  │
└────────────────┬────────────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────────────┐
│          Cloud Run / GKE (Kubernetes)                   │
└────────┬───────┬────────────────────────────────────────┘
         │       │
    ┌────▼──────▼─────┐
    │  Cloud SQL       │  PostgreSQL
    │  Memorystore     │  Redis
    │  Cloud Storage   │  Files
    └──────────────────┘
```

#### Step-by-Step GCP Deployment

##### 1. Set Up Project

```bash
gcloud projects create autoerp --name="AutoERP"
gcloud config set project autoerp
```

##### 2. Create Cloud SQL Instance

```bash
gcloud sql instances create autoerp-db \
  --database-version=POSTGRES_15 \
  --tier=db-custom-2-7680 \
  --region=us-central1 \
  --storage-type=SSD \
  --storage-size=100GB \
  --availability-type=regional
```

##### 3. Create Memorystore Redis

```bash
gcloud redis instances create autoerp-redis \
  --size=1 \
  --region=us-central1 \
  --redis-version=redis_7_0
```

##### 4. Create Storage Bucket

```bash
gsutil mb gs://autoerp-files
```

##### 5. Build and Deploy to Cloud Run

```bash
# Build container
gcloud builds submit --tag gcr.io/autoerp/app

# Deploy to Cloud Run
gcloud run deploy autoerp \
  --image gcr.io/autoerp/app \
  --platform managed \
  --region us-central1 \
  --allow-unauthenticated \
  --set-env-vars APP_ENV=production,DB_HOST=/cloudsql/autoerp:us-central1:autoerp-db
```

## Docker Production Deployment

### Production Docker Compose

Create `docker-compose.prod.yml`:

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile.prod
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
    restart: always
    volumes:
      - ./storage:/var/www/storage
    networks:
      - app-network

  nginx:
    image: nginx:alpine
    restart: always
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./docker/nginx/production.conf:/etc/nginx/conf.d/default.conf
      - ./public:/var/www/public
      - ./ssl:/etc/nginx/ssl
    networks:
      - app-network
    depends_on:
      - app

  db:
    image: postgres:15-alpine
    restart: always
    environment:
      POSTGRES_DB: ${DB_DATABASE}
      POSTGRES_USER: ${DB_USERNAME}
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    volumes:
      - db-data:/var/lib/postgresql/data
      - ./backups:/backups
    networks:
      - app-network

  redis:
    image: redis:7-alpine
    restart: always
    volumes:
      - redis-data:/data
    networks:
      - app-network

  queue:
    build:
      context: .
      dockerfile: Dockerfile.prod
    command: php artisan queue:work --tries=3 --timeout=90
    restart: always
    volumes:
      - ./storage:/var/www/storage
    networks:
      - app-network
    depends_on:
      - db
      - redis

volumes:
  db-data:
  redis-data:

networks:
  app-network:
    driver: bridge
```

### Production Dockerfile

Create `Dockerfile.prod`:

```dockerfile
FROM php:8.2-fpm-alpine AS builder

WORKDIR /var/www

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    postgresql-dev \
    oniguruma-dev \
    nodejs \
    npm

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    mbstring \
    zip \
    exif \
    pcntl \
    bcmath \
    gd \
    opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . /var/www

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction
RUN npm ci && npm run build

# Optimize
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Production stage
FROM php:8.2-fpm-alpine

WORKDIR /var/www

# Install runtime dependencies only
RUN apk add --no-cache \
    postgresql-libs \
    libpng \
    libzip \
    oniguruma

# Copy PHP extensions from builder
COPY --from=builder /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=builder /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d

# Copy application from builder
COPY --from=builder /var/www /var/www

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# Copy PHP configuration
COPY docker/php/production.ini /usr/local/etc/php/conf.d/production.ini

EXPOSE 9000

CMD ["php-fpm"]
```

## Kubernetes Deployment

### Kubernetes Manifests

Create `k8s/deployment.yaml`:

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: autoerp
  labels:
    app: autoerp
spec:
  replicas: 3
  selector:
    matchLabels:
      app: autoerp
  template:
    metadata:
      labels:
        app: autoerp
    spec:
      containers:
      - name: app
        image: your-registry/autoerp:latest
        ports:
        - containerPort: 9000
        env:
        - name: APP_ENV
          value: "production"
        - name: DB_HOST
          valueFrom:
            secretKeyRef:
              name: db-credentials
              key: host
        - name: DB_PASSWORD
          valueFrom:
            secretKeyRef:
              name: db-credentials
              key: password
        resources:
          requests:
            memory: "512Mi"
            cpu: "500m"
          limits:
            memory: "1Gi"
            cpu: "1000m"
        livenessProbe:
          httpGet:
            path: /api/health
            port: 9000
          initialDelaySeconds: 30
          periodSeconds: 10
        readinessProbe:
          httpGet:
            path: /api/health
            port: 9000
          initialDelaySeconds: 5
          periodSeconds: 5
```

Create `k8s/service.yaml`:

```yaml
apiVersion: v1
kind: Service
metadata:
  name: autoerp-service
spec:
  selector:
    app: autoerp
  ports:
    - protocol: TCP
      port: 80
      targetPort: 9000
  type: LoadBalancer
```

Create `k8s/ingress.yaml`:

```yaml
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: autoerp-ingress
  annotations:
    cert-manager.io/cluster-issuer: "letsencrypt-prod"
    nginx.ingress.kubernetes.io/ssl-redirect: "true"
spec:
  tls:
  - hosts:
    - autoerp.com
    secretName: autoerp-tls
  rules:
  - host: autoerp.com
    http:
      paths:
      - path: /
        pathType: Prefix
        backend:
          service:
            name: autoerp-service
            port:
              number: 80
```

### Deploy to Kubernetes

```bash
# Create namespace
kubectl create namespace autoerp

# Create secrets
kubectl create secret generic db-credentials \
  --from-literal=host=postgres-service \
  --from-literal=password=YourSecurePassword \
  -n autoerp

# Apply manifests
kubectl apply -f k8s/ -n autoerp

# Check deployment
kubectl get pods -n autoerp
kubectl get svc -n autoerp
```

## Environment Configuration

### Production .env

```env
APP_NAME="AutoERP"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://autoerp.com

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=your-db-host
DB_PORT=5432
DB_DATABASE=autoerp
DB_USERNAME=dbuser
DB_PASSWORD=secure_password

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=s3
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=your-redis-host
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@autoerp.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=autoerp-files

SANCTUM_STATEFUL_DOMAINS=autoerp.com
SESSION_DOMAIN=.autoerp.com

SENTRY_LARAVEL_DSN=

L5_SWAGGER_GENERATE_ALWAYS=false
```

## Performance Optimization

### OPcache Configuration

Create `docker/php/production.ini`:

```ini
[opcache]
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
opcache.fast_shutdown=1

[php]
memory_limit=512M
max_execution_time=300
post_max_size=50M
upload_max_filesize=50M
```

### Laravel Optimizations

```bash
# Production optimizations
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Clear development caches
php artisan optimize:clear
```

## Monitoring and Logging

### Application Performance Monitoring (APM)

**Install New Relic:**
```bash
composer require philkra/laravel-newrelic
```

**Configure in .env:**
```env
NEWRELIC_ENABLED=true
NEWRELIC_APP_NAME="AutoERP"
NEWRELIC_LICENSE_KEY=your-license-key
```

### Error Tracking with Sentry

```bash
composer require sentry/sentry-laravel
```

Configure in `.env`:
```env
SENTRY_LARAVEL_DSN=https://xxx@xxx.ingest.sentry.io/xxx
SENTRY_TRACES_SAMPLE_RATE=0.2
```

### Logging to ELK Stack

Install Filebeat on application servers to ship logs to Elasticsearch.

## Backup and Recovery

### Database Backups

**Automated Backup Script** (`scripts/backup-db.sh`):

```bash
#!/bin/bash
BACKUP_DIR="/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
DB_NAME="autoerp"
DB_USER="postgres"

pg_dump -U $DB_USER -h localhost $DB_NAME | gzip > $BACKUP_DIR/backup_$TIMESTAMP.sql.gz

# Keep only last 30 days of backups
find $BACKUP_DIR -type f -name "backup_*.sql.gz" -mtime +30 -delete
```

**Cron Job:**
```bash
0 2 * * * /path/to/scripts/backup-db.sh
```

### Application Backup

```bash
# Backup storage folder to S3
aws s3 sync /var/www/storage s3://autoerp-backups/storage/
```

## Security Hardening

### SSL/TLS Configuration

Use Let's Encrypt for free SSL certificates:

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d autoerp.com -d www.autoerp.com

# Auto-renewal
sudo crontab -e
# Add: 0 3 * * * certbot renew --quiet
```

### Firewall Configuration

```bash
# Allow only necessary ports
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### Security Headers

Add to Nginx configuration:

```nginx
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';" always;
```

## Continuous Deployment

### GitHub Actions Workflow

Create `.github/workflows/deploy.yml`:

```yaml
name: Deploy to Production

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Set up PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
    
    - name: Install dependencies
      run: composer install --no-dev --optimize-autoloader
    
    - name: Run tests
      run: php artisan test
    
    - name: Build Docker image
      run: docker build -t autoerp:${{ github.sha }} .
    
    - name: Push to registry
      run: |
        docker tag autoerp:${{ github.sha }} your-registry/autoerp:latest
        docker push your-registry/autoerp:latest
    
    - name: Deploy to Kubernetes
      run: |
        kubectl set image deployment/autoerp app=your-registry/autoerp:latest
        kubectl rollout status deployment/autoerp
```

## Post-Deployment Checklist

After deployment:

- [ ] Verify application is accessible
- [ ] Test user authentication
- [ ] Test API endpoints
- [ ] Check database connectivity
- [ ] Verify Redis is working
- [ ] Test file uploads (S3)
- [ ] Check email functionality
- [ ] Verify SSL certificate
- [ ] Test queue processing
- [ ] Check monitoring dashboards
- [ ] Verify backups are running
- [ ] Review error logs
- [ ] Performance test (load testing)
- [ ] Security scan

## Rollback Procedures

### Docker Rollback

```bash
# Rollback to previous image
docker-compose down
docker-compose up -d --build

# Or use specific tag
docker pull your-registry/autoerp:previous-tag
docker-compose up -d
```

### Kubernetes Rollback

```bash
# Rollback to previous revision
kubectl rollout undo deployment/autoerp -n autoerp

# Rollback to specific revision
kubectl rollout undo deployment/autoerp --to-revision=2 -n autoerp
```

### Database Rollback

```bash
# Restore from backup
gunzip < backup_20260131_020000.sql.gz | psql -U postgres autoerp
```

---

**Last Updated**: 2026-01-31
**Version**: 1.0.0
