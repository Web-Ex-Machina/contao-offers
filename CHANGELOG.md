Extension "Offers" for Contao Open Source CMS
========

2.3.0 - 2024-10-16
- ADDED : Add a new textarea attribute that allows you to use a tinyMCE or a HTML field as attribute
- ADDED : Ajax request + JS Promise to retrieve the number of items depending on frontend module + optional filters
- ADDED : Display mode for form inside the reader module
- ADDED : Internationalization support
- UPDATED : Bundle modernisation with Rector
- UPDATED : Better application management
- UPDATED : Better alerts management

2.2.0 - 2024-08-21
- ADDED : Add a new tag "countoffers", returns the number of published offers in one or several PIDs 
- ADDED : Add a new tag "offer", return a value for the current offer or for a specific ID
- ADDED : Add a frontend module to display an offer directly
- UPDATED : Move the filters into a dedicated frontend module

2.1.0 - 2024-08-05
- UPDATED: add return types and update composer dependency versions fo support Utils 2.0

2.0.2 - 2024-05-29
- FIXED: `seeOffer` button not correctly displayed

2.0.1 - 2024-02-29
- FIXED: Assets load earlier for lists with no offers (see https://github.com/Web-Ex-Machina/contao-offers/commit/ce6cc382ff7204d6e6e522a73bac1643bd334486)

2.0.0 - 2024-02-28
- UPDATED: bundle now requires [webexmachina/contao-utils](https://github.com/Web-Ex-Machina/contao-utils)
- UPDATED: Various fixes and changes in order to make this bundle more flexible

1.0.1 - 2022-03-16
- Update template & translations

1.0.0 - 2021-01-29
- Add default time for `postedAt` and `availableAt` fields

0.3 - 2020-05-23
- Add & refactor fields
- Sort applicants files into subfolders, using the job code
- Improve default layouts
- Apply phpcsfixer rules
- Use country-select plugin
- Improve modals behaviour
- Bugfixes

0.2 - 2020-02-07
- Add translations 
- Remove mandatory fields to make it easier to use

0.1 - 2019-03-27
- Rework the repo to make it generic