Be2bill backend simulation
========================

This app intent is to simulate be2bill backend for testing when the sandbox cant be used

Configure
---

    notification_url: ""
    template_url: ""
    template_mobile_url: ""
    be2bill_identifier: ""
    be2bill_password: ""
    return_url: ""

Run
---

Using [pourquoi/PaymentBe2billBundle](https://github.com/pourquoi/PaymentBe2billBundle) in your app, set the ```debug_base_url``` to ```http://localhost:8089/```

and launch the server

    app/console server:run localhost:8089
