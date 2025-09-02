# Weather Forecaster Agent - Cleanup Plan

## Duplicate/Empty Files Removed
- [x] APACHE-DEPLOYMENT.md (empty file)
- [x] FLY-IO-DEPLOY.md (empty file)
- [x] FLY-MANUAL-DEPLOY.md (empty file)
- [x] deployment-guide.md (redundant, points to DEPLOYMENT.md)
- [x] Dockerfile.apache (empty file)

## Deployment Scripts Consolidated
- [x] fly-deploy-admin.bat (removed, redundant)
- [x] fly-deploy-windows.bat (removed, redundant)
- [x] composer.json.fly (removed, not needed)

## Configuration Files Still To Clean Up
- [ ] fly.apache.toml (if not using Apache deployment)
- [ ] .env.fly (merge necessary configurations into main .env.example)

## Code Cleanup Completed
- [x] Dockerfile - Fixed duplicate directives and simplified
- [x] docker/start.sh - Fixed duplicate code blocks and removed PHP-FPM references

## Documentation Updates
- [x] Updated README.md to reflect the simplified deployment process
- [x] Updated TODO.md to reflect current project status
- [ ] Update DEPLOYMENT.md to remove references to unused deployment methods

## Further Steps
- [ ] Consider consolidating the remaining deploy scripts (deploy-docker.bat, deploy-docker.sh, fly-deploy.bat, fly-deploy.ps1, fly-deploy.sh)
- [ ] Remove any test or temporary files not needed for production
- [ ] Consider removing `google/apiclient` dependency if not used (check usage first)
- [ ] Clean up and consolidate fly.toml configuration files