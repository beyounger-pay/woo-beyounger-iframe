
console.log('===var_order_id===', plugin_name_ajax_object.var_order_id);
console.log('===var_base_url===', plugin_name_ajax_object.var_base_url);

var orderId = plugin_name_ajax_object.var_order_id;
var baseUrl = plugin_name_ajax_object.var_base_url || "https://api.beyounger.com"; //线上地址


(function () {
    try {
        function loadTpayJS(url, callback){
            var script = document.createElement('script');
            var fn = callback || function () {};
            script.type = 'text/javascript';
          
            // IE
            if (script.readyState) {
              script.onreadystatechange = function () {
                if( script.readyState === 'loaded' || script.readyState === 'complete' ){
                  script.onreadystatechange = null;
                  fn();
                }
              };
            } else {
              //其他浏览器
              script.onload = function () {
                fn();
              };
            }
            script.src = url;
            document.getElementsByTagName('head')[0].appendChild(script);
        }
        function initTPay(id) {
            orderId = orderId || id
            if(!orderId){
                console.log("orderId 为空")
                return ;
            }
            const publicKeyUrl = `${baseUrl}/v1/checkout?id=${orderId}`;
            const method = "card"
            const layout = "two-rows" //"three-rows"
            
        
            function initPayment({
                publicKey,
                returnUrl,
                clientToken,
                country,
                currency,
                billingDetails,
        
            }) {
        
                let isNeedBillingDetails = country == "US" || country == "CA"
        
                window.tpay.checkout({
                    callbacks: {
                        onPaymentError: ({ payload: error }) => {
                            console.log('Error occured', error.code, error.title, error.message);
                            window.location.href = `${returnUrl}`
                        },
                        onPaymentSuccess: (msg) => {
                            console.log("onPaymentSuccess:", msg)
                            window.location.href = `${returnUrl}`
                        },
                        onPaymentFail: (msg) => {
                            console.log("onPaymentFail:", msg)
                            window.location.href = `${returnUrl}`
                        },
                        onPaymentClicked: (e) => {
                            console.log('click', e)
                            // for validating card details alone.
                            if (isNeedBillingDetails) {
                                // for validating card details and billingDetails.
                                // for US and CA country, billingDetails is required. provide details as below.
                                window.tpay.validate({
                                    billingDetails,
                                })
                            }
                            else {
                                window.tpay.validate()
                            }
        
        
        
                        },
                        onPayButtonState: ({ payload: state }) => {
                            console.log('State changed', state.disabled, state.progress);
                            // window.document.getElementById("sub_order").disabled = state.disabled
                        },
                        onValidate: (results) => {
                            console.log('results', results)
                            const card = results.payload.card
                            const billingDetails = results.payload.billingDetails
                            if (isNeedBillingDetails && card.isValid && card.billingDetails) {
                                window.tpay.confirmCardPayment({
                                    clientToken,
                                    billingDetails,
                                })
        
                            }
                            else if (!isNeedBillingDetails && card.isValid) {
                                window.tpay.confirmCardPayment({
                                    clientToken,
                                })
                            }
                            else {
                                const cardErrors = results.payload.errors || []
                                const billingDetailsErrors = results.payload.errors || []
        
                                const errors = cardErrors.concat(billingDetailsErrors)
        
                                errors.forEach(element => {
                                    print(element)
                                });
        
                            }
                        }
                    },
                    payment: {
                        key: publicKey,
                        method,
                        currency, // collection currency
                        country,
                        layout, // optional
                    },
                    config: { // optional
                        // hideErrors: true
                        // customPayButton: true
                    },
                    style: {}, // optional
        
                })
        
            }
        
            function getPublickKeyMethod() {
                return new Promise(function (resolve, reject) {
                    fetch(publicKeyUrl, {
                        method: "GET", // *GET, POST, PUT, DELETE, etc.
                        headers: {
                            "Content-Type": "application/json",
                        },
                    })
                        .then((response) => response.json())
                        .then((result) => {
                            resolve(result);
                        })
                        .catch((error) => {
                            reject(error);
                        });
                });
            };
            
            getPublickKeyMethod().then((res) => {
                console.log(res);
                const { currency } = res.result
                const { channel_data = {}, customer = {} } = res.result.data
        
                const {
                    country,
                    address,
                    city,
                    state,
                    phone,
                    first_name,
                    last_name,
                    zipcode
                } = customer || {}
                const { script_url, client_token: clientToken, public_key: publicKey, return_url } = channel_data || {}
        
                
                const params = {
                    clientToken,
                    publicKey,
                    country,
                    currency,
                    billingDetails: {},
                    returnUrl: return_url
                }
        
        
        
        
                if (params.country == "US" || params.country == "CA") {
                    params.billingDetails = {
                        name: `${first_name}${last_name}`,
                        address: {
                            line1: address,
                            line2: address,
                            city,
                            state,
                            country,
                            postal_code: zipcode,
                        },
                        phone: {
                            country_code: "+1",
                            number: phone,
                        }
                    }
                }
        
        
                console.log(params)
                const isValid = params.publicKey && params.clientToken && params.returnUrl;
                console.log(isValid)
                if (isValid && script_url) {
                    // 根据接口加载当前环境需要的js地址 
                    loadTpayJS(script_url, () => {
                        window.tpay  = window.tazapay; 
                        initPayment(params);
                    })
                }
        
            });
        
        }

        window.initTPay = initTPay
        initTPay();
  
    } catch (e) {
      // Fail quietly
      console.log(e)
    }
})()