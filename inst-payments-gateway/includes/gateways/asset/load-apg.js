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
var tradeID = "";


// console.log('===var_order_id===11',plugin_name_ajax_object.var_order_id);

const initCard=() =>{

}
const baseUrl = "https://api-sandbox.beyounger.com"; //https://api-sandbox.beyounger.com
const tradeIDUrl = `${baseUrl}/v1/checkout`;

var getTradeIDMethod = () => {
    return new Promise(function (resolve, reject) {
        console.log('===var_order_id===',plugin_name_ajax_object.var_order_id, `${tradeIDUrl}?id=${plugin_name_ajax_object.var_order_id}`);
        // publicKeyUrl =
        fetch(`${tradeIDUrl}?id=${plugin_name_ajax_object.var_order_id}`, {
            method: "GET", // *GET, POST, PUT, DELETE, etc.
            headers: {
                "Content-Type": "application/json",
            },
        })
            .then((response) => response.json())
            .then((result) => {
                console.log("####Success:", result);
                console.log("####Success1:", result['result']['data']['channel_data']['tradeId']);
                //var tradeId = result['result']['data']['channel_data']['tradeId'];
                var tradeId = "8c24a84594074a8c55da8a2cae732813";
                console.log('tradeId 1',tradeId);
                if(tradeId){
                    resolve(tradeId);
                }
                else{
                    reject('error');
                }



            })
            .catch((error) => {
                console.error("####Error:", error);
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
    Frames.submitCard();
}





loadJS("https://cdn.checkout.com/js/framesv2.min.js", () => {
    console.log("js load");
    initCard();
});

// document.getElementById("my_place_order").addEventListener('click',(e)=>{
//     console.log('触发place_order')
//     submitCard(e)
// })


console.log('11111111')
const submitCardApg = () => {
    clickButton();
};
