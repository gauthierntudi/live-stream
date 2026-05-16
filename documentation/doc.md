Mobile application and website 
integrations
The integration of the MaxiCash platform supports 2 integration models: mobile applications (MaxiCash API) and websites (MaxiCash gateway). 
The MaxiCash gateway is primarily designed to allow simple and quick integration of the website with the MaxiCash platform. With the MaxiCash gateway, you can receive payments from MaxiCash users and others. The gateway also allows the merchant to receive payments from non-MaxiCash users using credit cards, Mobile Money, and others. 
The MaxiCash gateway includes e-commerce plugins for Wordpress/WooCommerce and Prestashop (1.6 and 1.7).

MaxiCash Gateway.
The MaxiCash gateway allows the merchant to collect payments into their MaxiCash account using multiple payment channels such as credit cards, MaxiCash, Mobile Money, and Mobile Banking.
Integration with MaxiCash Gateway can be done using the following methods:
Form Post
Posting to URL with QueryString
eCommerce Plugin (Wordpress/WooCommerce & Prestashop 1.6/1.7)
Form Post Payment
The FomPost method allows you to call the MaxiCash Gateway with an HTML form.
Post the form with the attributes below using these URLs:
    Test: https://api-testbed.maxicashapp.com/PayEntryPost
Live: https://api.maxicashapp.com/PayEntryPost
Here's how to use each parameter:
PayType : Always set to MaxiCash, unless otherwise specified. This parameter is mandatory.
Amount :The expected amount for the transaction. This amount will be credited to your MaxiCash  account. This parameter is mandatory. Please note that amounts are expected in cents. This means  that if you want to process a payment of 1 USD, you should send an amount of 100.
Currency: The currency of the transaction. This parameter is mandatory. Typically takes 5 values:  USD, ZAR, CDF, maxiRand, or maxiDollar.

Phone: The payer's phone number. This parameter is optional. Generally used for Mobile Money  payment methods.
E-mail : The payer's email address. This parameter is optional. Generally used for credit card payments.
Merchant ID: The merchant ID authenticates the merchant on the platform. This parameter is mandatory.
Merchant Password : The Merchant Password works in conjunction with the MerchantID to authenticate the merchant on the platform. This parameter is mandatory.
Language: Allows specifying a language on the gateway. This parameter is optional. Currently, only  English (en) and French (fr) are supported. When not specified, the system defaults to English Reference: This is a transaction reference used by the merchant. This parameter is mandatory.
acccepturl: This is the merchant's webpage to which the payer will be redirected when their payment is successful. This parameter is mandatory. The MaxiCash gateway will add some query  string parameters described below.
cancelurl : This is the merchant's webpage to which the payer will be redirected if they choose to  cancel the payment. This parameter is optional. The MaxiCash gateway will add some query string  parameters described below. If this parameter is not specified, MaxiCash will default to the decline  URL.
declineurl : This is the merchant's webpage to which the payer will be redirected in case of payment failure. MaxiCash also defaults to this parameter if the cancel URL is not specified. 
This parameter is mandatory. The MaxiCash gateway will add some query string parameters    described below.
notifyurl : This parameter informs the merchant site of the transaction status before the payer is r edirected to accepturl, declineurl, or failureurl. This parameter is optional but recommended. 

QueryString URL Payment
The queryString method allows you to call the MaxiCash Gateway with a JSON string in a Data parameter in the request URL.
Post the JSON in the Data parameter with the attributes below using these URLs: Test: https://api-testbed.maxicashapp.com/PayEntry
Live: https://api.maxicashapp.com/PayEntry

API MaxiCash
The MaxiCash API allows the merchant to receive payment into their MaxiCash account from a MaxiCash or non-MaxiCash user and to pay their partners into their MaxiCash account or their mobile money.
Note: ALL AMOUNTS WITH MAXICASH ARE IN CENTS, FOR EXAMPLE, TO MAKE A PAYMENT OF 1 USD, THE API MUST RECEIVE 100 CENTS IN THE REQUEST.
Fundraising (Credit/Debit Card)
Integration with the MaxiCash API for collecting funds can be done with one of these options: PayNowSync
PayNowAsynch
Pay Credit Card 

PayNowSynch
This method is used to process a synchronous MaxiCash payment from a website or mobile application. The method receives a currency, an amount, a phone number according to the chosen payment method (MaxiCash, Airtel money, Mpsa, Orange money, etc.), a payment method, a reference, and the language.
Initiate the payment with the PayNowSynch method and wait for the payment status.
The user will receive a notification on their phone according to the chosen payment method and  will have 60 seconds to approve the payment.
During a synchronous payment, the API waits for up to 420 seconds before the transaction expires.
Method POST 
Endpoint
Test : https://webapi-test.maxicashapp.com/Integration/PayNowSync Live: https://webapi.maxicashapp.com/Integration/PayNowSync

Request Body Structure 
Nom
Description
Type
Additional Information
MerchantID
Required for authenticating the request
String
MerchantPassw ord
Required to authenticate the request
String
RequestData
Contains the payment details
Dictio
nnaire
PayType
Defines the
customer's payment method
Intege
r
0 for Maxicash / 1 for Airtel money / 2 for Mpsa / 3 for
Orange money
Montant
The amount that the merchant
sends
String
Always in cents. If you want to
send 1
maxiDollar,
insert 100.
Reference
Téléphone
This is a payment reference
Phone number of the MaxiCash
user
String
String


Example of the request body
{
"RequestData": {
"Amount": "100",
"Reference": "tbnhgfcc",
"Telephone": "243824707127"
  },
"MerchantID": "XXXXXXXXXXXXXXXXXXXXXXX",,
"MerchantPassword": "xxxxxxxxxxxxxxxxxxxxxxxx",, "PayType": 0,
"CurrencyCode": "USD" // CDF pour franc congolais }
Example of the response body
{
"SessionToken": null,
"ResponseStatus": "Failed",
"ResponseError": "transaction failed", "ResponseData": "",
"ResponseDesc": "",
"TransactionID": null, "Reference": null
}



PayNowAsynch
This method is used to process an asynchronous MaxiCash payment, particularly payments made from a Mobile Banking on a website or mobile application. The method receives a currency, an amount, a phone number according to the chosen payment method (MaxiCash, Airtel money, Mpsa, Orange money, etc.), a payment method, a reference, and the language.
Initiate the payment with the PayNowAsynch method and wait for the payment status.
The user will receive an OTP and will have 60 seconds to approve the payment.
Complete the operation with the CompletePayLater method
During a synchronous payment, the API waits for up to 420 seconds before the transaction expires.
Method POST 
Endpoint
Test : https://webapi-test.maxicashapp.com/Integration/PayNowSync Live: https://webapi.maxicashapp.com/Integration/PayNowSync


NB: PayNowAsynch only supports payment via Mobile Banking. :
- RakkaCash from BGDFI BANK with PayType 51
Example of the request body
{
  "RequestData": {
"Amount": "100",
"Reference": "", votre reference
"Telephone": "243820000000"
  },
"MerchantID": "",
"MerchantPassword": "",
"PayType": 51,
"CurrencyCode": "USD" 
}
Example of the response body
{
"SessionToken": null,
"ResponseStatus": "", // status of the request    
 "ResponseError": "", // raison of the failure of the request  "ResponseData": "",
"ResponseDesc": "", "TransactionID":"", "Reference": null
}


CompletePayLater
This method is used to validate the payment with the OTP received from PayNowAsynch.
Method POST 
Endpoint
Test : https://webapi-test.maxicashapp.com/Integration/PayNowSync
Live: https://webapi.maxicashapp.com/Integration/PayNowSync
Example of the request body
{
    "PIN": "537733", // the otp from the PayNowAsynch
    "TransactionID": "MC638319278922464540", TransactionID received from PayNowAsynch     "MerchantID": " MerchantID ",
    "MerchantPassword": " MerchantPassword ",     "UserToken": ""
}


Pay Credit Card
This method is used to process a credit card payment from a website or mobile application.. 
Initiate the payment with the PayCreditCard method, and you will receive a 'pending' status and a  payment link in the ResponseData.
Method POST 
Endpoint
Test: https://webapi-test.maxicashapp.com/Integration/PayCreditCard 
Live: https://webapi.maxicashapp.com/Integration/PayCreditCard
Request Body Structure 
Example of the request body
{
"PayType": "MaxiCash",
"MerchantID": "****",
"MerchantPassword": "******",
"Amount": "300",
"Currency": "USD",
"Telephone": "243820000000",
"Language": "fr",
 "Reference": "REF09766789",
 "SuccessURL": " Your redirection link if the request is success ", "FailureURL": " Your redirection link if the request is Faild",
"CancelURL": " Your redirection link if the request is Cancel ", "NotifyURL": " ",
"FirstName": "firstname",
"LastName": "lastname",
"Email": "payer’s Email ",
"cData": {
"cDate": "12/2023",
"cNumber": "4000000000000002",
"vCVV": "123"
    }
}
Example of the response body
{
"SessionToken": "",
"ResponseStatus": "pending",
"ResponseError": "",
"ResponseData": "https://mpi.quipugmbh.com/index.
jsp?ORDERID=1994364&SESSIONID=8E32F8AF8FA9E5D2B4A658C59C10D9D5", "ResponseDesc": "",
 "TransactionID": "", "Reference": null
}


Other important Endpoint
Check Payement by Reference
This method is used to check the status of a payment with a reference.
Method GET 
Endpoint
Test:
https://webapi-test.maxicashapp.com/Integration/ CheckPaymentStatusByReference
Live:
https://webapi.maxicashapp.com/Integration/ CheckPaymentStatusByReference

Example of the request body
{
"MerchantID": "***",
"MerchantPassword": "*****",
"Reference": "votre reference",
"TransactionID": ""
}

Check the validity of a generated token
This method is used to verify the validity of a generated token. 
Method GET 
Endpoint
Test:
https://webapi-test.maxicashme.com/Integration/IsTokenValid?Token={Token} 
Live:
https://webapi.maxicashme.com/Integration/IsTokenValid?Token={Token}  