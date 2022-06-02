# Tst2Lrs ILIAS Plugin

## Requirements

Install the [Lp2Lrs Plugin](https://github.com/internetlehrer/Lp2Lrs) first and configure a learning record store.

## Installation

Start at your ILIAS root directory.

```bash
mkdir -p Customizing/global/plugins/Services/EventHandling/EventHook
cd Customizing/global/plugins/Services/EventHandling/EventHook
git clone https://github.com/spyfly/Tst2Lrs.git
composer install
```

Then update and activate the plugin in the ILIAS Plugin Administration

## Requirements

* ILIAS 6.0 - 7.999
* PHP >=7.0
