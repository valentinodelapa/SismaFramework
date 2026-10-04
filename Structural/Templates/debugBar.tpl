<style>
    .debug-bar{
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        font-size: 12px;
        line-height: 1.4;
        bottom: 0px;
        width: 100%;
        position: fixed;
        display: flex;
        flex-direction: column;
        z-index: 10000;
    }
    .debug-bar-header, .debug-bar-header-left, .debug-bar-header-right{
        display: flex;
        flex-direction: row;
    }
    .debug-bar-header{
        height: 0px;
        max-height: 0px;
        border-top: 5px solid #a9a9a9;
        padding: 0px 10px;
        justify-content: space-between;
        background-color: #dbdbdb;
        transition: max-height 1s ease;
    }
    .debug-bar:hover .debug-bar-header{
        height: auto;
        max-height: 50px;
    }
    .debug-bar-header-left{
        justify-content: flex-start;
    }
    .debug-bar-header-left > label, .debug-bar-header > div{
        margin: 2px 5px;
    }
    .debug-bar-header-left > div{
        cursor: pointer
    }
    .debug-bar-header-right > div{
        margin: 0px 1px;
    }
    .debug-bar-header-left > label{
        padding: 2px 0px;
    }
    .debug-bar-header-left > div, .debug-bar-header-right > div{
        font-weight: 600;
        font-variant-numeric: tabular-nums;
        padding: 2px 5px;
        background-color: #f4f4f4;
        border-radius: 3px;
    }
    .debug-bar-header-left > label:last-of-type{
    }
    .debug-bar-body, .debug-bar-body > pre{
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
        font-size: 12px;
        line-height: 1.5;
    }
    .debug-bar-body{
        height: 200px;
        display: none;
        flex-direction: column;
        background-color: white;
        overflow-y: scroll
    }
    .debug-bar-body > pre {
        margin: 0px;
        padding: 0px;
    }
    .debug-bar-body > pre > div{
        padding: 2px 10px;
    }
    .debug-bar-body > pre > div:nth-of-type(odd){
        background-color: #ffffff
    }
    .debug-bar-body > pre > div:nth-of-type(even){
        background-color: #f4f4f4
    }
</style>
<div class="debug-bar">
    <div class="debug-bar-header">
        <div class="debug-bar-header-left">
            <label>Database</label>
            <div class="debug-information-label"
                 data-body-type="debug-body-database">{{queryExecutedNumber}}</div>
            <label>Log</label>
            <div class="debug-information-label"
                 data-body-type="debug-body-log">{{logRowNumber}}</div>
            <label>Form</label>
            <div class="debug-information-label"
                 data-body-type="debug-body-form">{{formFilterNumber}}</div>
            <label>Variables</label>
            <div class="debug-information-label"
                 data-body-type="debug-body-vars">{{varsNumber}}</div>
        </div>
        <div class="debug-bar-header-right">
            <div>{{memoryUsed}} MB</div>
            <div>{{executionTime}} ms</div>
        </div>
    </div>
    <div class="debug-bar-body debug-body-database">
        <pre>{{debugBarQuery}}</pre>
    </div>
    <div class="debug-bar-body debug-body-log">
        <pre>{{debugBarLog}}</pre>
    </div>
    <div class="debug-bar-body debug-body-form">
        <pre>{{debugBarForm}}</pre>
    </div>
    <div class="debug-bar-body debug-body-vars">
        <pre>{{debugBarVars}}</pre>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const debugBar = document.querySelector('.debug-bar');
        const debugInformationLabels = document.querySelectorAll('.debug-information-label');
        for (const debugInformationLabel of debugInformationLabels) {
            debugInformationLabel.addEventListener('click', function () {
                const bodyInformationType = this.dataset.bodyType;
                const debugBarBodies = document.querySelectorAll('.debug-bar-body');

                for (const debugBarBody of debugBarBodies) {
                    if (!debugBarBody.classList.contains(bodyInformationType)) {
                        debugBarBody.style.display = 'none';
                    }
                }
                const targetDebugBarBody = debugBar.querySelector('.' + bodyInformationType);
                targetDebugBarBody.style.display = getComputedStyle(targetDebugBarBody).display === 'none' ? 'block' : 'none';
            });
        }
        const body = document.querySelector('body');
        body.appendChild(debugBar);
    });
</script>
