Be2bill backend simulation
========================

Configure app/parameters.yml
---

    notification_url: "" # transaction notifications will be posted to your app to this url
    template_url: "" # url to your custom template for the payment form
    template_mobile_url: "" # url to your custom template for the mobile payment form
    be2bill_identifier: ""
    be2bill_password: ""
    return_url: "" # redirect the client to this url after the form payment process


Run
---

	app/console server:run localhost:8089


Configure your app
---

for example using [pourquoi/PaymentBe2billBundle](https://github.com/pourquoi/PaymentBe2billBundle), set the ```debug_base_url``` to ```http://localhost:8089/```

