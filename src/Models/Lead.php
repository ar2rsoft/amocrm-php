<?php

namespace AmoCRM\Models;

use AmoCRM\Models\Traits\SetNote;
use AmoCRM\Models\Traits\SetTags;
use AmoCRM\Models\Traits\SetDateCreate;
use AmoCRM\Models\Traits\SetLastModified;

/**
 * Class Lead
 *
 * Класс модель для работы со Сделками
 *
 * @package AmoCRM\Models
 * @author dotzero <mail@dotzero.ru>
 * @link http://www.dotzero.ru/
 * @link https://github.com/dotzero/amocrm-php
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Lead extends AbstractModel
{
    use SetNote, SetTags, SetDateCreate, SetLastModified;

    /**
     * @var array Список доступный полей для модели (исключая кастомные поля)
     */
    protected $fields = [
        'name',
        'date_create',
        'last_modified',
        'status_id',
        'pipeline_id',
        'sale',
        'contacts_id',
        'responsible_user_id',
        'created_user_id',
        'request_id',
        'linked_company_id',
        'tags',
        'visitor_uid',
        'notes',
        'modified_user_id',
    ];

    /**
     * Список сделок
     *
     * Метод для получения списка сделок с возможностью фильтрации и постраничной выборки.
     * Ограничение по возвращаемым на одной странице (offset) данным - 500 сделок
     *
     * @link https://developers.amocrm.ru/rest_api/leads_list.php
     * @param array $parameters Массив параметров к amoCRM API
     * @param null|string $modified Дополнительная фильтрация по (изменено с)
     * @return array Ответ amoCRM API
     */
    public function apiList($parameters, $modified = null)
    {
        $response = $this->getRequest('/api/v2/leads', $parameters, $modified);

        return isset($response['items']) ? $response['items'] : [];
    }

    /**
     * Добавление сделки
     *
     * Метод позволяет добавлять сделки по одной или пакетно
     *
     * @link https://developers.amocrm.ru/rest_api/leads_set.php
     * @param array $leads Массив сделок для пакетного добавления
     * @return int|array Уникальный идентификатор сделки или массив при пакетном добавлении
     */
    public function apiAdd($leads = [])
    {
        if (empty($leads)) {
            $leads = [$this];
        }

        $parameters = [
            'add' => [],
        ];

        foreach ($leads AS $lead) {
            $parameters['add'][] = $lead->getValues();
        }

        $response = $this->postRequest('/api/v2/leads', $parameters);

        if (isset($response['items'])) {
            $result = array_map(function($item) {
                return $item['id'];
            }, $response['items']);
        } else {
            return [];
        }

        return count($leads) == 1 ? array_shift($result) : $result;
    }

    /**
     * Обновление сделки
     *
     * Метод позволяет обновлять данные по уже существующим сделкам
     *
     * @link https://developers.amocrm.ru/rest_api/leads_set.php
     * @param int $id Уникальный идентификатор сделки
     * @param string $modified Дата последнего изменения данной сущности
     * @return bool Флаг успешности выполнения запроса
     * @throws \AmoCRM\Exception
     */
    public function apiUpdate($id, $modified = 'now')
    {
        $this->checkId($id);

        $parameters = [
            'update' => [],
        ];

        $lead = $this->getValues();
        $lead['id'] = $id;
        $lead['last_modified'] = strtotime($modified);

        $parameters['update'][] = $lead;

        $response = $this->postRequest('/api/v2/leads', $parameters);

        return empty($response['leads']['update']['errors']);
    }
}
