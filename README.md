TMEIT Wiki
==========

This is fork of [MediaWiki](https://www.mediawiki.org/) with extensions and adaptations for 
[TMEIT](http://tmeit.se). TMEIT is a social group and part of the 
[Chapter for Information- and Nanotechnology](http://insektionen.se) for students at The 
Royal Institute of Technology (KTH).

_Det här är en fork av [MediaWiki](https://www.mediawiki.org/) med tillägg och anpassningar för 
[TMEIT](http://tmeit.se). TMEIT är en studiesocial nämnd i 
[Sektionen för Informations- och Nanoteknik](http://insektionen.se) för studenter på 
Kungliga Tekniska Högskolan, KTH._

## Status
Live on `TMEIT.se` but not 100% working as of yet. All code has been migrated from the old repository
but due to changes in the way MediaWiki handles sessions and authentication, stuff is broken and
is in the process of being un-broken.

## Development plan
See above.

See the project [Issues](https://github.com/wsv-accidis/tmeit-wiki/issues) for the current 
development backlog. This includes bugs as well as enhancements and upcoming features.

This project is open-source to facilitate contributions. Note: The main branch is *tmeit-master*. 
The *master* branch holds the version of MediaWiki that we are currently using as a base.

## Licensing
This code is distributed according to the terms of the **GNU General Public License, 
version 2 or later**.

## Acknowledgements
This fork integrates the following third-party extensions to MediaWiki:

* [HTMLets by Daniel Kinzler](https://www.mediawiki.org/wiki/Extension:HTMLets)
* [ParserFunctions by Tim Starling](https://www.mediawiki.org/wiki/Extension:ParserFunctions)

In addition, the custom SAML authentication extension (`TmeitSamlAuth`) was developed through inspiration from:

* [SimpleSamlAuth by Jørn Åne](https://www.mediawiki.org/wiki/Extension:SimpleSamlAuth)
* [SAMLAuth by Piers Harding](https://www.mediawiki.org/wiki/Extension:SAMLAuth)

Thanks and acknowledgements also go to:

* [Browser Ponies by Mathias Panzenböck](http://panzi.github.io/Browser-Ponies/)
* [SimpleSAMLphp by UNINETT](https://simplesamlphp.org/) and other contributors
* [Wikimedia Enginering](https://www.mediawiki.org/wiki/Wikimedia_Engineering) and other contributors for the fantastic [MediaWiki](https://www.mediawiki.org/)! 
