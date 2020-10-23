### 1.0.0 ###
* custom implementation of `Magento\Framework\MessageQueue\ConsumerInterface` making possible injection of envelope callback,
which allows to introduce custom message consumption easily without copy-paste of whole class
* a few implementations of `LizardMedia\MessageQueue\Queue\Consumer\EnvelopeCallback\EnvelopeCallbackInterface`, each handling
message in its specific way, including `x-death` parameters support
