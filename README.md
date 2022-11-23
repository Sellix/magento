# Magento Extension

![](https://img.shields.io/badge/Sellix-Magento-blueviolet) ![](https://img.shields.io/badge/Version-v1.0.0-green)

<p align="center">
  <img src="https://cdn.sellix.io/cdn-cgi/image/w=500,h=500/static/logo/main.png" alt="Sellix Logo"/>
</p>

Magento extension to use Sellix as a Payment Gateway.

# Installation

0. **Download** the latest release ZIP [on GitHub](https://github.com/Sellix/magento/releases).

1. **Upload** the code to folder app/code/Sellix/Pay

1. Enter following commands to **install** module:
   php bin/magento setup:upgrade
   php bin/magento setup:static-content:deploy

2. **Enable** and **configure** Sellix Pay in Magento Admin under `Stores -> Configuration-> Sales -> Payment Methods -> Sellix Pay`