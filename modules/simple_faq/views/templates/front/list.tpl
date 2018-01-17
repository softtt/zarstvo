
<script type="text/javascript">
    var questions_controller_url = '{$questions_controller_url}';
</script>
<div>
    <h3 class="page-product-heading" id="question-heading">Вопросы и ответы</h3>

    {if isset($questions) && count($questions)}
        <div class="questions">
            {foreach from=$questions item=question}
                {if $question.question}
                    <div class="question row">
                        <div class="question_details col-sm-12">
                            <div class="question_author_infos">
                                <h4> <strong>{$question.customer_name|escape:'html':'UTF-8'}</strong></h4>
                            </div>

                            <label>Вопрос</label>
                            <p>{$question.question|escape:'html':'UTF-8'|nl2br}</p>

                            <label>Ответ</label>
                            {$question.answer}
                        </div><!-- .question_details -->
                    </div><!-- .question -->
                {/if}
            {/foreach}
        </div><!-- .questions -->
    {else}
        {if ! isset($logged) or ! $logged}
        <p class="warning alert alert-warning">Еще нет вопросов к этому товару. Чтобы задать вопрос необходимо быть зарегистрированным пользователем.</p>
        {/if}
    {/if}

    {* Add question form *}

    <div id="add-question-box">
        {if isset($logged) AND $logged}
        <div id="question-errors" class="alert alert-danger" style="display: none;"><ul></ul></div>

        <p class="alert alert-success" id="question-success" style="display: none;">Вопрос успешно добавлен. Мы ответим в ближайшее время.</p>

        <form class="std clearfix" id="add-question-form" action="#">
            <fieldset>
                <div>
                    <h4 class="page-product-heading">Задать вопрос</h4>

                    {*
                    {if isset($errors) && count($errors)}
                        <div class="alert alert-danger">
                            <ol>
                            {foreach from=$errors key=k item=error}
                                <li>{$error}</li>
                            {/foreach}
                            </ol>
                        </div>
                    {/if}
                    *}

                    <div class="form-group">
                        <label for="customer_name">Ваше имя</label>
                        <input type="text" class="form-control" name="customer_name" id="customer_name"
                            value="{if isset($post_name)}{$post_name|escape:'html':'UTF-8'}{else}{if isset($logged) AND $logged}{$customerName|escape:'html':'UTF-8'}{/if}{/if}" />
                    </div>
                    <div class="form-group">
                        <label for="email">Ваш email</label>
                        <input type="text" class="form-control" name="email" id="email"
                            value="{if isset($post_email)}{$post_email|escape:'html':'UTF-8'}{else}{if isset($logged) AND $logged AND isset($email) AND $email}{$email|escape:'html':'UTF-8'}{/if}{/if}" />
                    </div>
                    <div class="form-group">
                        <input id="id_product_send" name="id_product" type="hidden" value='{$id_product_form}' />
                        <input id="id_image_send" name="id_image" type="hidden" value='{$id_image_form}' />
                    </div>
                    <div class="form-group">
                        <label for="question">Вопрос</label>
                        <textarea class="form-control" id="question" name="question" cols="26" rows="5">{if isset($post_question)}{$post_question|escape:'html':'UTF-8'}{/if}</textarea>
                    </div>
                    <p class="cart_navigation required submit clearfix">
                        <button  type="submit" class="button btn btn-default button-medium" id="submitNewQuestion">
                        <span>
                            Задать вопрос
                        </span>
                        </button>
                    </p>
                </div>
            </fieldset>
        </form>

        {/if}
    </div>
    <!-- #add-question -->
</div>
