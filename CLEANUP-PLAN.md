# Project Cleanup Plan

During the deployment troubleshooting process, we created numerous files to test different approaches. Now that the application has been successfully deployed to Fly.io, we should clean up these temporary files.

## Files to Remove

### Deployment Scripts
- [ ] `fly-deploy.sh` - Bash script for Fly.io deployment
- [ ] `fly-deploy.bat` - Windows batch script for Fly.io deployment
- [ ] `fly-deploy-admin.bat` - Administrator version of the Fly.io deployment script
- [ ] `fly-deploy.ps1` - PowerShell script for Fly.io deployment
- [ ] `fly-deploy-windows.bat` - Alternative Windows batch script
- [ ] `install-fly-cli.bat` - Fly CLI installation script for Windows
- [ ] `install-fly-cli.sh` - Fly CLI installation script for Bash

### Alternative Configurations
- [ ] `Dockerfile.apache` - Apache-based alternative Dockerfile
- [ ] `fly.apache.toml` - Apache-based Fly.io configuration
- [ ] `docker/apache/vhost.conf` - Apache virtual host configuration

### Duplicate Documentation
- [ ] `FLY-MANUAL-DEPLOY.md` - Manual deployment instructions
- [ ] `APACHE-DEPLOYMENT.md` - Apache-based deployment instructions

## Files to Consolidate

### Deployment Documentation
- [ ] Consolidate all Fly.io deployment guides into a single comprehensive document
- [ ] Update `deployment-guide.md` to focus only on the successful deployment method
- [ ] Keep only the relevant sections in `docs/fly-io-github-deployment.md`

### Configuration Files
- [ ] Keep only the necessary Fly.io configuration in `fly.toml`
- [ ] Clean up any redundant sections in the Dockerfile

## Directories to Clean Up
- [ ] `.fly` directory - Keep only the necessary configuration
- [ ] Remove any other temporary directories created during deployment testing

## Files to Keep
- [x] `Dockerfile` - Main Docker configuration
- [x] `fly.toml` - Main Fly.io configuration
- [x] `docker/nginx` - Nginx configuration
- [x] `docker/php` - PHP configuration
- [x] `docker/start.sh` - Container startup script
- [x] `DEPLOYMENT.md` - Main deployment documentation
- [x] `.platform` - Platform identification marker

This cleanup will make the codebase more maintainable and reduce confusion for future developers.
