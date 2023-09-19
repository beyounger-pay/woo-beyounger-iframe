const loadJS = (url, callback) => {
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
console.log('===js===');
const tokenUrl = "https://api.sandbox.checkout.com/tokens";
const baseUrl = "https://api-sandbox.beyounger.com";
const publicKeyUrl = `${baseUrl}/v1/checkout?id=23090805000915505`;

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

getPublickKeyMethod().then((res) => {
    console.log(res);
    publicKey = "pk_sbox_m3dhzcsbmlxmnqypu6zii5j5nec";
});

const initCard = () => {
    console.log('initCard', 11111);
    if (publicKey) {
        console.log('initCard', publicKey);
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
            publicKey = "pk_sbox_m3dhzcsbmlxmnqypu6zii5j5nec";

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
    console.log(token);
};

function submitCard(e) {
    e.preventDefault();
    if (loading) {
        return;
    }

    console.log("err", err);
    if (err) {
        console.log("err");
        return;
    }
    loading = true;
    document.getElementById("pay-button").disabled = true;
    document.getElementById("pay-button").innerText = "Loading";
    Frames.submitCard();
}






