# Yireo_StoreViewCommands

<!-- badges.specs.start -->
<div class="github-specs-badges">

![Magento version](https://img.shields.io/badge/Magento-2.4.6%20%7C%202.4.9-orange)
![PHP version](https://img.shields.io/badge/PHP-8.2%E2%80%938.5-777BB4)
![License](https://img.shields.io/badge/License-OSL--3.0-blue)
![Latest Version](https://img.shields.io/packagist/v/yireo/magento2-store-view-commands)

</div>
<!-- badges.specs.end -->


**This Magento 2 module adds a couple of CLI commands for managing Store Views**

## Installation
```bash
composer require yireo/magento2-store-view-commands
bin/magento module:enable Yireo_StoreViewCommands
```

## Usage
Create a new StoreView:
```bash
bin/magento store:view:create
```

Update an existing StoreView:
```bash
bin/magento store:view:update
```

## Current status

<!-- badges.test.start -->
<div class="github-pipeline-badges">

![Static Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_StoreViewCommands/static-tests.yml?label=static-tests)
![Unit Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_StoreViewCommands/unit-tests.yml?label=unit-tests)
![Integration Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_StoreViewCommands/integration-tests.yml?label=integration-tests)
![Playwright](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_StoreViewCommands/playwright.yml?label=playwright)
![DI Compilation](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_StoreViewCommands/compile.yml?label=compile)

</div>
<!-- badges.test.end -->
