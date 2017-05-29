<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Model_base extends CI_Model
{
    protected $table;
    protected $default_limit = 20;

    public $_database;

    protected $_db_group;

    protected $primary_key = 'seq';

    // soft delete
    protected $soft_delete = FALSE;
    protected $soft_delete_key = 'deleted';
    protected $_temporary_with_deleted = FALSE;

    // hook
    protected $before_create = [];
    protected $after_create = [];
    protected $before_update = [];
    protected $after_update = [];
    protected $before_get = [];
    protected $after_get = [];
    protected $before_delete = [];
    protected $after_delete = [];
    protected $callback_parameters = [];


    function __construct()
    {
        parent::__construct();

        $this->_set_database();
    }

    // 조건 없으면 전체 카운팅, 있으면 해당 조건 카운팅
    function get_count($parameters = null)
    {
        if (!$parameters)
            return $this->_database->count_all($this->table);

        $this->_database->where($parameters);
        $this->_database->from($this->table);
        return $this->_database->count_all_results();
    }

    // 여러 결과 리턴
    function get_many($parameters = null)
    {
        $result = null;

        if (!is_array($parameters)) {

            $ret = null;
            // 조건 불가
            if (is_numeric($parameters))
                return null;
            // 모든 레코드
            else if (!$parameters)
                $result = $this->_database->get($this->table)->result();
            // 특정 조건만
            else
                $result = $this->_database->where($parameters)->get($this->table)->result();
        } else {

            $condition = $parameters[0];

            if ($condition)
                $this->_database->where($condition);

            if ($parameters['select'])
                $this->_database->select($parameters['select']);

            if ($parameters['order'])
                $this->_database->order_by($parameters['order']);

            if ($parameters['group'])
                $this->_database->group_by($parameters['group']);

            if ($parameters['limit']) {

                if ($parameters['offset'])
                    $this->_database->limit($parameters['limit'], $parameters['offset']);
                else
                    $this->_database->limit($parameters['limit']);
            }

            $result = $this->_database->get($this->table)->result();
        }

        foreach ($result as $key => &$row)
            $row = $this->trigger('after_get', $row, ($key == count($result) - 1));

        return $result;
    }

    function get_one($parameters = null)
    {
        $result = null;

        // PK 찾기 또는 조건만 검색
        if (!is_array($parameters)) {

            if (is_numeric($parameters))
                $result = $this->_database->where($this->primary_key, $parameters)->get($this->table)->row();
            else
                $result = $this->_database->where($parameters)->get($this->table)->row();

            if (!$result)
                return null;
        } else {

            $condition = $parameters[0];

            if ($condition) {
                if (is_numeric($condition))
                    $this->_database->where($this->primary_key, $condition);
                else
                    $this->_database->where($condition);
            }

            if ($parameters['select'])
                $this->_database->select($parameters['select']);

            if ($parameters['order'])
                $this->_database->order_by($parameters['order']);

            $result = $this->_database->get($this->table)->row();

            if (!$result)
                return null;
        }

        $result = $this->trigger('after_get', $result);

        return $result;
    }

    function delete($parameters = null)
    {

        if (!$parameters)
            return 0;

        // PK 조건 또는 일반 조건
        if (is_numeric($parameters))
            $this->_database->where($this->primary_key, $parameters);
        else if (is_string($parameters))
            $this->_database->where($parameters);
        else
            return 0;

        $this->_database->delete($this->table);

        return $this->_database->affected_rows();
    }

    function save($parameters = null)
    {
        if (!$parameters || !is_array($parameters))
            return null;

        $condition = $parameters[0];

        // create
        if (!$condition) {

            $parameters['created_at'] = $parameters['updated_at'] = date('Y-m-d H:i:s');

            $this->_database->insert($this->table, $parameters);

            $result = $this->_database->insert_id();

            return $result;
        } // update
        else {

//            $parameters = $this->trigger('before_update', $parameters);
            unset($parameters[0]);

            // PK 조건 또는 일반 조건
            if (is_numeric($condition))
                $this->_database->where($this->primary_key, $condition);
            else
                $this->_database->where($condition);

            $parameters['updated_at'] = date('Y-m-d H:i:s');
            $this->_database->update($this->table, $parameters);
//            $this->trigger('after_update', array($data, $result));

            return $this->_database->affected_rows();
        }
    }

    function get_primary_key()
    {
        return $this->primary_key;
    }

    /**
     * PK로 row 반환
     *
     */
    public function get($primary_value)
    {
        if ($this->soft_delete && $this->_temporary_with_deleted !== TRUE) {
            $this->_database->where($this->soft_delete_key, FALSE);
        }

        $row = $this->_database->where('seq', $primary_value)
            ->get($this->table)
            ->row();

        return $row;
    }

    function find_one($options = array())
    {
        $infos = $this->finds($options);
        if (!$infos) return NULL;
        if (count($infos) == 0) return NULL;

        return $infos[0];
    }

    function finds($options = array())
    {
        if ($this->soft_delete && $this->_temporary_with_deleted !== TRUE) {
            $this->_database->where($this->soft_delete_key, FALSE);
        }

        if (!isset($options['search'])) $options['search'] = array();

        $query = $this->_database->where($options['search']);

        if (!isset($options['or_where'])) $options['or_where'] = array();

        foreach ($options['or_where'] as $column_name => $column_values) {
            foreach ($column_values as $column_value) {
                $query = $this->_database->or_where($column_name, $column_value);
            }
        }

        if (isset($options['like'])) {
            $query = $query->like($options['like']);
        }

        if (isset($options['or_like'])) {
            $query = $query->or_like($options['or_like']);
        }

        if (isset($options['in'])) {
            foreach ($options['in'] as $field => $values)
                $query = $query->where_in($field, $values);
        }

        if (isset($options['not_in'])) {
            foreach ($options['not_in'] as $field => $values)
                $query = $query->where_not_in($field, $values);
        }

        if (isset($options['order_by'])) {
            $query = $query->order_by($options['order_by']);
        }

        if (isset($options['select_max'])) {
            $query = $query->select_max($options['select_max']);
        }

        if (isset($options['group_by'])) {
            $query = $query->group_by($options['group_by']);
        }

        if (isset($options['select'])) {
            $select_query = '';
            $i = 0;
            foreach ($options['select'] as $column_name) {
                if ($i == 0) {
                    $select_query .= $column_name;
                } else {
                    $select_query .= ", $column_name";
                }

                ++$i;
            }

            $query = $query->select($select_query);
        }

        $limit = 0;
        if (isset($options['limit'])) $limit = $options['limit'];
        if (!isset($options['offset'])) $options['offset'] = 0;

        if ($limit > 0)
            $query = $query->get($this->table, $options['limit'] + 0, $options['offset'] + 0);
        else
            $query = $query->get($this->table);

        if (!$query) {
            //Fatal error: Call to a member function result() on a non-object in /var/www/thanks-ci/thanks-package/models/model_base.php on line 260
            // 예외 처리 필요함
            throw new Exception(var_export($options), 1);
        } else {
            $result = $query->result();
        }

        return $result;
    }

    /**
     * 데이터 삽입
     *
     */
    function create($data, $options = array())
    {
        if (is_object($data))
            $data = (array)$data;

        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = $data['created_at'];

        if (!is_array($data) && $data == FALSE) {
            return FALSE;
        }

        if (!$this->_database->insert($this->table, $data)) {
            $msg = $this->_database->display_error();

            throw new Exception($msg);
        }

        $result = $this->_database->insert_id();

        return $result;
    }

    /**
     * PK에 해당하는 row 갱신
     *
     */
    function update_tmp($id, $data, $options = array())
    {
        if (!$id) {
            $msg = 'PK값이 올바르지 않습니다.';

            throw new Exception($msg);
        }

        $result = $this->_database->where(array($this->get_primary_key() => $id))
            ->set('updated_at', "'" . date('Y-m-d H:i:s') . "'", FALSE)
            ->update($this->table, $data);

        $result = $this->_database->affected_rows();

        return $result;
    }

    /**
     * PK에 해당하는 row의 특정 필드 갱신
     *
     */
    function update_field($id, $field, $value)
    {
        if ($id === null) {
            $msg = 'PK값이 올바르지 않습니다.';

            throw new Exception($msg);
        }

        $data[$field] = $value;
        $data['updated_at'] = date('Y-m-d H:i:s');

        $result = $this->_database->where(array($this->get_primary_key() => $id))->update($this->table, $data);

        return $result;
    }

    /**
     * PK에 해당하는 row 삭제
     *
     */
    function delete_org_tmp($id)
    {
        $this->_database->where($this->get_primary_key(), $id);

        if ($this->soft_delete) {
            $result = $this->_database->update($this->table, array($this->soft_delete_key => TRUE));
        } else {
            $result = $this->_database->delete($this->table);
        }

        $result = $this->_database->affected_rows();

        return $result;
    }

    function update_field_by_name($column_name, $id, $field, $value)
    {
        $data[$field] = $value;

        $result = $this->_database
            ->where(array($column_name => $id))
            ->update($this->table, $data);

        return $result;
    }

    /**
     * table 명 반환
     *
     */
    function get_table()
    {
        return $this->table;
    }

    // 지정된 db group 이 있는지 체크해서 사용하도록 함, 없으면 기본 디비그룹 로딩
    private function _set_database()
    {
        if ($this->_db_group !== NULL) {
            $this->_database = $this->load->database($this->_db_group, TRUE, TRUE);
        } else {

            if (!isset($this->db) OR !is_object($this->db)) {
                $this->load->database('', FALSE, TRUE);
            }

            $this->_database = $this->db;
        }
    }

    public function delete_by_finds($where)
    {
        $infos = $this->finds($where);

        foreach ($infos as $key => $row) {
            $this->delete($row->seq);
        }
    }

    /**
     * 패스워드 문자열 생성
     */
    public function PASSWORD($text)
    {
        $text = '00' . $text . '_CT9#6d6@@';

        return md5($text);
    }

    public function trigger($event, $data = FALSE, $last = TRUE)
    {
        if (isset($this->$event) && is_array($this->$event)) {

            foreach ($this->$event as $method) {

                if (strpos($method, '(')) {
                    preg_match('/([a-zA-Z0-9\_\-]+)(\(([a-zA-Z0-9\_\-\., ]+)\))?/', $method, $matches);
                    $method = $matches[1];
                    $this->callback_parameters = explode(',', $matches[3]);
                }

                $data = call_user_func_array(array($this, $method), array($data, $last));
            }
        }

        return $data;
    }

}
