# N-Genius Magento Plugin

![Banner](assets/banner.jpeg)

## Installation

For more detailed instructions on setting up the N-Genius Magento plugin, please refer to
our [documentation.](https://docs.ngenius-payments.com/docs/magento-245)

#### Install using Composer (recommended)

- From the Magento root directory, install the N-Genius module
  using ```composer require networkinternational/ngenius```.

#### Install using FTP/SFTP

- Download ***ngenius-magento-plugin-master.zip*** and unzip it to a directory on your local machine.
- Use an FTP tool (such as *FileZilla*) to upload the contents of ```ngenius-magento-plugin-master``` directory to
  the ```app\code\NetworkInternational\NGenius\```directory of your Magento installation.
- From the Magento root directory, install N-Genius dependencies
  using ```composer require ngenius/ngenius-common:v1.0.8```.

### Magento Build Steps

- ```bin/magento module:enable NetworkInternational_NGenius```
- ```bin/magento setup:upgrade```
- ```bin/magento setup:di:compile```
- ```bin/magento indexer:reindex```
- ```bin/magento cache:clean```

## Download

You can download the latest version of the plugin from
our [Github releases page](https://github.com/network-international/ngenius-magento-plugin/releases)
