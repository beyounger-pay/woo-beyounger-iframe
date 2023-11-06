var loadJS = (url, callback) => {
    var script = document.createElement('script');
    var fn = callback || function () {
    };
    script.type = 'text/javascript';

    // IE
    if (script.readyState) {
        script.onreadystatechange = function () {
            if (script.readyState === 'loaded' || script.readyState === 'complete') {
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
let err = true;
let loading = false;
let publicKey = "";
//页面顶部
let cko_cardholder_name = document.getElementById("cko_cardholder_name");
let billing_first_name = document.getElementById("billing_first_name");
let billing_last_name = document.getElementById("billing_last_name");

let old_billing_first_name = billing_first_name.value;
let old_billing_last_name = billing_last_name.value;

if (old_billing_last_name && old_billing_first_name) {
    document.getElementById(
        "cko_cardholder_name"
    ).value = `${old_billing_first_name} ${old_billing_last_name}`;
}

billing_last_name.addEventListener("input", (e) => {
    let current_name = document.getElementById("cko_cardholder_name").value;
    let old_name = `${billing_first_name.value} ${old_billing_last_name}`;
    let new_name = `${billing_first_name.value} ${e.target.value}`;

    if (current_name === "" || current_name === old_name) {
        document.getElementById("cko_cardholder_name").value = new_name;
    }

    old_billing_last_name = e.target.value;
});

billing_first_name.addEventListener("input", (e) => {
    let current_name = document.getElementById("cko_cardholder_name").value;
    let old_name = `${old_billing_first_name} ${billing_last_name.value}`;
    let new_name = `${e.target.value} ${billing_last_name.value}`;

    if (current_name === "" || current_name === old_name) {
        document.getElementById("cko_cardholder_name").value = new_name;
    }

    old_billing_first_name = e.target.value;
});



console.log('===var_api_key===',plugin_name_ajax_object.var_api_key);
let apiKey = plugin_name_ajax_object.var_api_key;

console.log('===js===',apiKey);
const tokenUrl = "https://api.checkout.com/tokens"; //https://api.sandbox.checkout.com/tokens
const baseUrl = "https://api.beyounger.com"; //https://api-sandbox.beyounger.com
const publicKeyUrl = `${baseUrl}/v1/saas/checkout?apiKey=`+apiKey;

const getPublickKeyMethod = () => {
    return new Promise(function (resolve, reject) {
        fetch(publicKeyUrl, {
            method: "GET", // *GET, POST, PUT, DELETE, etc.
            headers: {
                "Content-Type": "application/json",
            },
        })
            .then((response) => response.json())
            .then((result) => {
                console.log("Success:", result);
                resolve(result);
            })
            .catch((error) => {
                console.error("Error:", error);
                reject(error);
            });
    });
};

const addEventHandler = () => {
    Frames.addEventHandler(
        Frames.Events.FRAME_VALIDATION_CHANGED,
        (event) => {
            var e = event.element;
            console.log("Frames.Events.FRAME_VALIDATION_CHANGED", e);
            document.getElementById("api_err_msg").innerText = "";
            if (event.isValid || event.isEmpty) {
                if (e === "card-number" && !event.isEmpty) {
                    document.getElementById("card_err_msg").innerText = "";
                } else if (e === "expiry-date") {
                    document.getElementById("date_err_msg").innerText = "";
                } else if (e === "cvv") {
                    document.getElementById("cvv_err_msg").innerText = "";
                }
            } else {
                if (e === "card-number") {
                    let msg = "Please enter a valid card number";
                    document.getElementById("card_err_msg").innerText = msg;
                    err = false;
                } else if (e === "expiry-date") {
                    let msg = "Please enter a valid expiry date";
                    document.getElementById("date_err_msg").innerText = msg;
                } else if (e === "cvv") {
                    let msg = "Please enter a valid cvv code";
                    document.getElementById("cvv_err_msg").innerText = msg;
                    err = false;
                }
            }
        }
    );

    function onCardTokenizationFailed(error) {
        console.log("CARD_TOKENIZATION_FAILED: %o", error);
        loading = false;
        document.getElementById("pay-button").disabled = false;
        document.getElementById("pay-button").innerText = "Pay";
        Frames.enableSubmitForm();
    }

    Frames.addEventHandler(Frames.Events.CARD_VALIDATION_CHANGED, () => {
        console.log("!Frames.isCardValid", !Frames.isCardValid());
        document.getElementById("pay-button").disabled =
            !Frames.isCardValid();
        err = !Frames.isCardValid();
    });

    Frames.addEventHandler(
        Frames.Events.CARD_TOKENIZATION_FAILED,
        onCardTokenizationFailed
    );
    Frames.addEventHandler(Frames.Events.CARD_TOKENIZED, (event) => {
        console.log("event.token", event.token);
        submitResult(event.token);
    });
};

const initCard = () => {
    if (publicKey) {
        Frames.init({
            publicKey: publicKey,
            localization: {
                cardNumberPlaceholder: "Card number",
                expiryMonthPlaceholder: "MM",
                expiryYearPlaceholder: "YY",
                cvvPlaceholder: "CVV",
            },
        });
        addEventHandler();
    } else {
        getPublickKeyMethod().then((res) => {
            console.log(res);
            publicKey = res.result.api_key

            Frames.init({
                publicKey: publicKey,
                localization: {
                    cardNumberPlaceholder: "Card number",
                    expiryMonthPlaceholder: "MM",
                    expiryYearPlaceholder: "YY",
                    cvvPlaceholder: "CVV",
                },
            });
            addEventHandler();
        });
    }
};

const submitResult = (token) => {
    document.getElementById("api_err_msg").innerText = "";
    loading = false;
    document.getElementById("pay-button").disabled = false;
    document.getElementById("pay-button").innerText = "Pay";
    Frames.enableSubmitForm();
    if(!token){
        return;
    }
    document.getElementById("js_var").value = token;
    document.getElementById("place_order").click()
};

function submitCard(e) {
    // e.preventDefault();
    if (loading) {
        return;
    }

    console.log("err", err);
    if (err) {
        document.getElementById("api_err_msg").innerText = "请填写信息";
        console.log("err");
        return;
    }
    document.getElementById("api_err_msg").innerText = "";
    loading = true;
    document.getElementById("pay-button").disabled = true;
    document.getElementById("pay-button").innerText = "Loading";
    let name = document.getElementById("cko_cardholder_name").value;
    if (name !== "") {
        Frames.cardholder = {
            name,
        };
    }
    Frames.submitCard();
}


getPublickKeyMethod().then((res) => {
    console.log(res);
    publicKey = res.result.api_key
});
console.log('load------js')

loadJS("https://cdn.checkout.com/js/framesv2.min.js", () => {
    console.log("js load");
    initCard();
});

// document.getElementById("my_place_order").addEventListener('click',(e)=>{
//     console.log('触发place_order')
//     submitCard(e)
// })