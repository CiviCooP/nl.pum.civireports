{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{if (!$chartEnabled || !$chartSupported )&& $rows}
    {if $pager and $pager->_response and $pager->_response.numPages > 1}
        <div class="report-pager">
            {include file="CRM/common/pager.tpl" location="top" noForm=0}
        </div>
    {/if}
    <table class="report-layout display">
        {capture assign="tableHeader"}
            {foreach from=$columnHeaders item=header key=field}
                {assign var=class value=""}
                {if $header.type eq 1024 OR $header.type eq 1 OR $header.type eq 512}
                {assign var=class value="class='reports-header-right'"}
                {else}
                    {assign var=class value="class='reports-header'"}
                {/if}
                {if !$skip}
                   {if $header.colspan}
                       <th colspan={$header.colspan}>{$header.title}</th>
                      {assign var=skip value=true}
                      {assign var=skipCount value=`$header.colspan`}
                      {assign var=skipMade  value=1}
                   {else}
                       <th {$class}>{$header.title}</th>
                   {assign var=skip value=false}
                   {/if}
                {else} {* for skip case *}
                   {assign var=skipMade value=`$skipMade+1`}
                   {if $skipMade >= $skipCount}{assign var=skip value=false}{/if}
                {/if}
            {/foreach}
        {/capture}

        {if !$sections} {* section headers and sticky headers aren't playing nice yet *}
            <thead class="sticky">
            <tr>
                <th></th>
                {$tableHeader}
        </tr>
        </thead>
        {/if}

        {* pre-compile section header here, rather than doing it every time under foreach *}
        {capture assign=sectionHeaderTemplate}
            {assign var=columnCount value=$columnHeaders|@count}
            {assign var=l value=$smarty.ldelim}
            {assign var=r value=$smarty.rdelim}
            {foreach from=$sections item=section key=column name=sections}
                {counter assign="h"}
                {$l}isValueChange value=$row.{$column} key="{$column}" assign=isValueChanged{$r}
                {$l}if $isValueChanged{$r}

                    {$l}if $sections.{$column}.type & 4{$r}
                        {$l}assign var=printValue value=$row.{$column}|crmDate{$r}
                    {$l}elseif $sections.{$column}.type eq 1024{$r}
                        {$l}assign var=printValue value=$row.{$column}|crmMoney{$r}
                    {$l}else{$r}
                        {$l}assign var=printValue value=$row.{$column}{$r}
                    {$l}/if{$r}

                    <tr><th colspan="{math equation="x + y" x=$columnCount y=1}">
                        <h{$h}>{$section.title}: {$l}$printValue|default:"<em>none</em>"{$r}
                            ({$l}sectionTotal key=$row.{$column} depth={$smarty.foreach.sections.index}{$r})
                        </h{$h}>
                    </th></tr>
                    {if $smarty.foreach.sections.last}
                        <tr>{$l}$tableHeader{$r}</tr>
                    {/if}
                {$l}/if{$r}
            {/foreach}
        {/capture}

        {foreach from=$rows item=row key=rowid}
           {eval var=$sectionHeaderTemplate}
            <tr  class="{cycle values="odd-row,even-row"} {$row.class} crm-report" id="crm-report_{$rowid}">
                <td>
                        <span id="projectintake_anamonreport{$row.case_id}_show">
                            <a href="#" onclick="
                                    projectintake_anamonreportCaseDetails('{$row.case_id}','{$row.customer_id}');
                                    showCaseActivities('{$row.case_id}');
                                    return false;"
                                    ><img src="{$config->resourceBase}i/TreePlus.gif" class="action-icon" alt="{ts}open section{/ts}"/></a>
                        </span>
                        <span id="minusprojectintake_anamonreport{$row.case_id}_hide" style="display: none;">
                            <a href="#" onclick="hideCaseActivities('{$row.case_id}'); return false;">
                                <img src="{$config->resourceBase}i/TreeMinus.gif" class="action-icon" alt="{ts}open section{/ts}"/>
                            </a>
                        </span>
                </td>
                {foreach from=$columnHeaders item=header key=field}
                    {assign var=fieldLink value=$field|cat:"_link"}
                    {assign var=fieldHover value=$field|cat:"_hover"}
                    <td class="crm-report-{$field}{if $header.type eq 1024 OR $header.type eq 1 OR $header.type eq 512} report-contents-right{elseif $row.$field eq 'Subtotal'} report-label{/if}">
                        {if $row.$fieldLink}
                            <a title="{$row.$fieldHover}" href="{$row.$fieldLink}">
                        {/if}

                        {if $row.$field eq 'Subtotal'}
                            {$row.$field}
                        {elseif $header.type & 4 OR $header.type & 256}
                            {if $header.group_by eq 'MONTH' or $header.group_by eq 'QUARTER'}
                                {$row.$field|crmDate:$config->dateformatPartial}
                            {elseif $header.group_by eq 'YEAR'}
                                {$row.$field|crmDate:$config->dateformatYear}
                            {else}
                                {if $header.type & 4}
                                   {$row.$field|truncate:10:''|crmDate}
                                {else}
                                   {$row.$field|crmDate}
                                {/if}
                            {/if}
                        {elseif $header.type eq 1024}
                            {if $currencyColumn}
                                <span class="nowrap">{$row.$field|crmMoney:$row.$currencyColumn}</span>
                            {else}
                                <span class="nowrap">{$row.$field|crmMoney}</span>
                           {/if}
                        {else}
                            {$row.$field}
                        {/if}

                        {if $row.$fieldLink}</a>{/if}
                    </td>
                {/foreach}
            </tr>
            </tr>
            <tr id="projectintake_anamonreport_{$row.case_id}_hide" class="crm-case_{$row.case_id} crm-report" style="display: none;">
                <td></td>
                <td colspan="{$columnCount}" width="99%" class="enclosingNested crm-case_{$row.case_id}">
                    <div id="projectintake_anamon-report-casedetails-{$row.case_id}"></div>
                </td>
            </tr>
        {/foreach}

        {if $grandStat}
            {* foreach from=$grandStat item=row*}
            <tr class="total-row">
                {foreach from=$columnHeaders item=header key=field}
                    <td class="report-label">
                        {if $header.type eq 1024}
                            {$grandStat.$field|crmMoney}
                        {else}
                            {$grandStat.$field}
                        {/if}
                    </td>
                {/foreach}
            </tr>
            {* /foreach*}
        {/if}
    </table>
    {if $pager and $pager->_response and $pager->_response.numPages > 1}
        <div class="report-pager">
            {include file="CRM/common/pager.tpl"  noForm=0}
        </div>
    {/if}
{/if}

{strip}
{*include activity view js file*}
{include file="CRM/common/activityView.tpl" list='report'}
<div id="view-activity-report">
    <div id="activity-content-report"></div>
</div>
{/strip}

{* Build case details*}
{literal}
<script type="text/javascript">
    function projectintake_anamonreportCaseDetails( caseId, contactId)
    {
        var dataUrl = "{/literal}{crmURL p='civicrm/case/details' h=0 q='snippet=4&caseId='}{literal}" + caseId +'&cid=' + contactId+'&type=report';
        cj.ajax({
            url     : dataUrl,
            dataType: "html",
            timeout : 5000, //Time in milliseconds
            success : function( data ){
                cj( '#projectintake_anamon-report-casedetails-'+caseId ).html( data );
            },
            error   : function( XMLHttpRequest, textStatus, errorThrown ) {
                console.error( 'Error: '+ textStatus );
            }
        });
    }

    function showCaseActivities( caseId) {
        cj('#projectintake_anamon-report-casedetails-'+ caseId).show();
        cj('#projectintake_anamonreport_'+caseId+'_hide').show();
        cj('#projectintake_anamonreport'+caseId+'_show').hide();
        cj('#minusprojectintake_anamonreport'+caseId+'_hide').show();
    }

    function hideCaseActivities( caseId) {
        cj('#projectintake_anamon-report-casedetails-'+ caseId).hide();
        cj('#projectintake_anamonreport_'+caseId+'_hide').hide();
        cj('#projectintake_anamonreport'+caseId+'_show').show();
        cj('#minusprojectintake_anamonreport'+caseId+'_hide').hide();
    }

</script>
{/literal}