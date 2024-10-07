# Yii2-usermanager
Extended User Manager

  Under constrution
## Planned services & solution

### Frontend elements
* **Registration**
  * Simple registration form (email address only)
  * Numerical Token confirmation via email (token is configurable default: 3 letter-4 number DYN-2234)
  * Initial password setup (see: Change/ edit password)
  * Initial profile setup (see: Profile)
* **Login**
  *  Login form with email & password
  *  Login via social (OAUTH etc.)
*  **Recovery** 
    *  Form for e-mail address
    *  Send email with Numerical Token if user exists
    *  Password setup form
*  **Change/edit password**
  * password form vith validation (configurable the neccessary length, letter and characters)
    * Letter Lowercase
    * Letter Uppercase
    * Numbers
    * Special Characters   
  * Client side validation is visually displayed 
* **Public profile**
  * Default profile fields
    * Nickname
    * Phone (default)
    * Avatar
    * Introdution text
  * Extra (Configurable) profile fields
* **Account (logged user)**
  * Account type and comparison
  * Payment status/process
  * Messages /notifcation about account
### Backend 
* User management
* RBAC
* Account type and services
* Billing
