# Tst2Lrs ILIAS Plugin

## Installation

Start at your ILIAS root directory. It is assumed the generated downloaded plugin `tst2lrs.zip` is in your download folder `~/Downloads`. Otherwise please adjust the commands below

Run the follow commands:

```bash
mkdir -p Customizing/global/plugins
cd Customizing/global/plugins
mv ~/Downloads/tst2lrs.zip tst2lrs.zip
unzip tst2lrs.zip
unlink tst2lrs.zip
```

Update and activate the plugin in the ILIAS Plugin Administration

Look after `TODO`'s in the plugin code. May you can remove some files (For example config) depending on your use. Also override this initial Readme

## Requirements

* ILIAS 6.0 - 7.999
* PHP >=7.0
