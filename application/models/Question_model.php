<?php
defined('BASEPATH') or exit('No direct script access allowed');
// Move to model folder
class Question_model extends CI_Model
{
    public function CreateQuestion()
    {
        // Question must be array
        $questions = $this->input->post('questions');
        $data = array();
        foreach ($questions as $question) {
            $data[] = array(
                'question_text' => $question,
            );
        }
        $this->_insertBatchQuestion($data);
    }

    public function ViewQuestion()
    {
        $question = $this->_getQuestion();
        return $question;
    }

    public function getAnswerId($id)
    {
        $this->db->select('*');
        $this->db->from('answers');
        $this->db->where('user_id', $id);
        $query = $this->db->get();
        return $query->result();
    }

    public function checkUserAnswer($answerId)
    {
        $this->db->select('*');
        $this->db->from('answer_details');
        $this->db->where('answer_id	', $answerId);
        $this->db->order_by('id', 'desc');
        $this->db->limit(1);
        $query = $this->db->get();
        return $query->result();
    }

    public function countAnswered($answerId)
    {
        $this->db->select('count(*) as count');
        $this->db->from('answer_details');
        $this->db->where('answer_id', $answerId);
        $query = $this->db->get();
        return $query->result();
    }

    public function getFirstQuestion()
    {
        $this->db->select('questioners.*, topics.title, topics.sub_title');
        $this->db->from('questioners');
        $this->db->join('topics', 'questioners.topic_id = topics.id');
        $this->db->order_by('questioners.id', 'asc');
        $this->db->limit(1);
        $query = $this->db->get();
        return $query->row(); // Return the first row as an object
    }

    // Fetch the next question based on the last question ID
    public function getCurrentQuestion($lastQuestionId)
    {
        $this->db->select('questioners.*, topics.title, topics.sub_title');
        $this->db->from('questioners');
        $this->db->join('topics', 'questioners.topic_id = topics.id');
        $this->db->where('questioners.id >', $lastQuestionId); // Fetch next question after the last question ID
        $this->db->order_by('questioners.id', 'asc');
        $this->db->limit(1);
        $query = $this->db->get();
        return $query->row(); // Return the next row as an object
    }

    public function getQuestionnaireWithAverage()
    {
        $this->db->select('questioners.id, questioners.questioner_text, COUNT(answer_details.questioners_id) AS answer_count, AVG(answer_details.value) AS average_value');
        $this->db->from('questioners');
        $this->db->join('answer_details', 'questioners.id = answer_details.questioners_id', 'left');
        $this->db->group_by('questioners.id, questioners.questioner_text');
        $query = $this->db->get();
        $results = $query->result();

        return $results;
    }

    public function getQuestionnaireWithAverageByTopicId($topics)
    {
        $this->db->select('questioners.id, questioners.questioner_text, COUNT(answer_details.questioners_id) AS answer_count, AVG(answer_details.value) AS average_value');
        $this->db->from('questioners');
        $this->db->join('answer_details', 'questioners.id = answer_details.questioners_id', 'left');
        $this->db->where('topic_id', $topics);
        $this->db->group_by('questioners.id, questioners.questioner_text');
        $query = $this->db->get();
        $results = $query->result();

        return $results;
    }
    public function getAllTopic(){
        $this->db->select('topics.*');
        $this->db->from('topics');
        $this->db->join('questioners', 'questioners.topic_id = topics.id');
        $this->db->group_by('topics.id');
        $query = $this->db->get();
        $results = $query->result();

        return $results;
    }
    public function getAvfByTopic()
    {
        $query = $this->db->select('questioners.id, questioners.questioner_text AS question_title, COUNT(answer_details.questioners_id) AS answer_count, AVG(answer_details.value) AS average_value')
            ->from('questioners')
            ->join('answer_details', 'questioners.id = answer_details.questioners_id', 'left')
            ->group_by('questioners.id, questioners.questioner_text')
            ->get();

        $result = $query->result_array();
    }
    public function getChartValue(){
        $sql = "
            SELECT
                t.id AS topic_id,
                t.sub_title AS topic_title,
                AVG(CASE WHEN ad.value = 1 THEN ad.value ELSE 0 END) AS avg_value_1,
                AVG(CASE WHEN ad.value = 2 THEN ad.value ELSE 0 END) AS avg_value_2,
                AVG(CASE WHEN ad.value = 3 THEN ad.value ELSE 0 END) AS avg_value_3,
                AVG(CASE WHEN ad.value = 4 THEN ad.value ELSE 0 END) AS avg_value_4,
                AVG(CASE WHEN ad.value = 5 THEN ad.value ELSE 0 END) AS avg_value_5
            FROM
                topics t
            JOIN
                questioners q ON q.topic_id = t.id
            JOIN
                answer_details ad ON ad.questioners_id = q.id
            LEFT JOIN
                answers a ON ad.answer_id = a.id
            GROUP BY
                t.id, t.title;
        ";
        $query = $this->db->query($sql);
        $result = $query->result_array();

        $data = [];
        foreach($result as $row){
            $data[] = [
                'topic_name' => $row['topic_title'],
                'avg_value_1' => $row['avg_value_1'],
                'avg_value_2' => $row['avg_value_2'],
                'avg_value_3' => $row['avg_value_3'],
                'avg_value_4' => $row['avg_value_4'],
                'avg_value_5' => $row['avg_value_5'],
            ];
            
        }
        return $data;
    }
    
    public function getTopics(){
        $this->db->select('*');
        $this->db->from('topics');
        $query = $this->db->get();
        return $query->result();
    }

    public function save_question($data) {
        // Insert question data into the database
        $this->db->insert('questioners', $data);
    }

    public function get_question_by_id($id) {
        // Get question by ID from the database
        $this->db->where('id', $id);
        return $this->db->get('questioners')->row();
    }

    public function update_question($id, $data) {
        // Update question by ID in the database
        $this->db->where('id', $id);
        $this->db->update('questioners', $data);
    }

    public function delete_question($id) {
        // Delete question by ID from the database
        $this->db->where('id', $id);
        $this->db->delete('questioners');
    }

    public function save_topic($data) {
        // Insert topic data into the database
        $this->db->insert('topics', $data);
    }

    public function get_topic_by_id($id) {
        // Get topic by ID from the database
        $this->db->where('id', $id);
        return $this->db->get('topics')->row();
    }

    public function update_topic($id, $data) {
        // Update topic by ID in the database
        $this->db->where('id', $id);
        $this->db->update('topics', $data);
    }

    public function delete_topic($id) {
        // Delete question by ID from the database
        $this->db->where('id', $id);
        $this->db->delete('topics');
    }
    

    private function _insertBatchQuestion($data)
    {
        $this->db->insert_batch('questioners', $data);
    }

    private function _getQuestion()
    {
        $this->db->select('questioners.questioner_text, questioners.id,questioners.indicator, topics.title, topics.sub_title');
        $this->db->from('questioners');
        $this->db->join('topics', 'questioners.topic_id = topics.id');
        $this->db->order_by('questioners.id', 'asc');
        $this->db->order_by('questioners.id', 'asc');
        $query = $this->db->get();
        return $query->result();
    }




}
