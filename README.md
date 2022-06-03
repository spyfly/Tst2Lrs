# Tst2Lrs ILIAS Plugin

## About
This is an ILIAS-Plugin for exporting test progress and results data to a LRS. 

For a more detailed description, refer to the [documentation](docs/README.md).

## Requirements

Install the [Lp2Lrs Plugin](https://github.com/internetlehrer/Lp2Lrs) first and configure a learning record store.

## Installation

Start at your ILIAS root directory.

```bash
mkdir -p Customizing/global/plugins/Services/EventHandling/EventHook
cd Customizing/global/plugins/Services/EventHandling/EventHook
git clone https://github.com/spyfly/Tst2Lrs.git
cd Tst2Lrs
composer install
cd /var/www/html/ilias # modify ILIAS install dir as necessary
git apply Customizing/global/plugins/Services/EventHandling/EventHook/Tst2Lrs/ilias.patch
```

Then update and activate the plugin in the ILIAS Plugin Administration

## Requirements

* ILIAS 6.0 - 7.999
* PHP >=7.0