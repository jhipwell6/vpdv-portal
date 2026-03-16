<?php

namespace FXUP_User_Portal\Models;

class Villa
{
    private $post_id;
    private $post_object;
    private $Itinerary;
    private static $RoomClass = 'FXUP_User_Portal\Models\Room';
    private static $ItineraryClass = 'FXUP_User_Portal\Models\Itinerary';
    private $raw_rooms;
    private $Rooms;
    private $bedrooms;
    private $sleeps;
    private $image_link;
    private $permalink;
    private $faqs;
    private $title;
    private $introduction_video;
    private $video_thumbnail_url;
    private $video_title;
    private $hot_spots_link;

    public function __construct($post_object_or_id, $Itinerary = null, $options = array()) {

        if (is_numeric($post_object_or_id)) {
            $this->post_id = $post_object_or_id;
                    // Post Object
            $args = array(
                'post_type' => 'villa',
                'p' => $this->post_id
            );

            $query = new \WP_Query($args);
            $post_object = (!empty($query->posts[0])) ? $query->posts[0] : false;
            if (false === $post_object) {
                throw new \Exception("No Villa post found with ID: $this->post_id");
            }
            $this->post_object = $post_object;
            return $this->post_object;
        } else {
            if (!$post_object_or_id instanceof \WP_Post) {
                throw new \Exception("Invalid post object passed to Villa constructor.");
            }
            $this->post_object = $post_object_or_id;
            $this->post_id = $this->post_object->ID;
        }

        $this->setItinerary($Itinerary);

        return $this;
    }

    public function getItinerary() {
        if (null === $this->Itinerary) {
            $this->setItinerary(false);
        }
        return $this->Itinerary;
    }

    public function setItinerary($Itinerary) {
        if ($Itinerary instanceof self::$ItineraryClass) {
            $this->Itinerary = $Itinerary;
        } else {
            $this->Itinerary = false;
        }
        return $this->Itinerary;
    }

    public function getPostID() {
        return $this->post_id;
    }

    public function getPostObject() {
        return $this->post_object;
    }

    // Number field
    public function getBedrooms() {
        if (null === $this->bedrooms) {
            $this->bedrooms = get_field('villa_bedrooms', $this->getPostID());
        }
        return $this->bedrooms;
    }

    public function getSleeps() {
        if (null === $this->sleeps) {
            $this->sleeps = get_field('villa_sleeps', $this->getPostID());
        }
        return $this->sleeps;
    }

    public function getHotSpotsLink() {
        if (null === $this->hot_spots_link) {
            $this->hot_spots_link = get_field('hot_spots_link', $this->getPostID());
        }
        return $this->hot_spots_link;
    }

    public function getImageLink() {
        if (null === $this->image_link) {
            $this->image_link = get_the_post_thumbnail_url($this->getPostID());
        }
        return $this->image_link;
    }

    public function getPermalink() {
        if (null === $this->permalink) {
            $this->permalink = get_the_permalink($this->getPostID());
        }
        return $this->permalink;
    }

    public function getFAQs() {
        if (null === $this->faqs) {
            $raw_faqs = get_field('questions_&_answers', $this->getPostID());
            if (!is_array($raw_faqs)) {
                $raw_faqs = array();
            }
            $this->faqs = $raw_faqs;
        }
        return $this->faqs;
    }

    public function getTitle() {
        if (null === $this->title) {
            $this->title = get_the_title($this->getPostID());
        }
        return $this->title;
    }
    
    public function getVideoURL() 
    {
        if (null === $this->introduction_video) {
            $this->introduction_video = get_field( 'introductory_video', $this->getPostID() );
        }
        return $this->introduction_video;
    }

    public function getVideoEmbedURL( $iframe = false ) 
    {   
        return self::generate_youtube_embed_url( $this->getVideoURL(), $iframe );
    }

    public function getVideoThumbnailURL() 
    {
        if (null === $this->video_thumbnail_url) {
            $video_thumbnail = get_field( 'introductory_video_thumbnail', $this->getPostID() );
            if( isset($video_thumbnail['url']) ) {
                $this->video_thumbnail_url = $video_thumbnail['url'];
            }
        }
        return $this->video_thumbnail_url;
    }

    public function getVideoTitle()
    {
        if (null === $this->video_title) {
            $this->video_title = get_field( 'introductory_video_title', $this->getPostID() );
        }
        return $this->video_title;
    }

    /* BEGIN ROOMS */

    public function addRoom($name)
    {
        // Suggested implementation below...
        /*        
        $Room = self::$RoomClass::create($room_name, $SubVilla, $Villa = $this, $options = array());

        if (! is_array($this->Rooms)) {
            $this->Rooms = array();
        }
        $this->Rooms[] = $Room;
        return $Room;
        */
    }

    public function getRawRooms() {
        if (null === $this->raw_rooms) {
            $raw_rooms = get_field('room', $this->getPostID());
            $this->raw_rooms = $raw_rooms;
        }
        return is_array($this->raw_rooms) ? $this->raw_rooms : array(); // Always array
    }

    public function getRooms()
    {
        if (null === $this->Rooms) {

            $raw_rooms = $this->getRawRooms();
            $Rooms = array();
            foreach ($raw_rooms as $index => $raw_room) {

                $room_name = $raw_room['room_name'];
                $sub_villa_id = $raw_room['room_villa'];
                if ($sub_villa_id instanceof \WP_Post) {
                    $sub_villa_id = $sub_villa_id->ID;
                }
                $SubVilla = new self($sub_villa_id, $this->getItinerary()); // Watch what value is returned by getItinerary - should be false if No Itinerary.
                // public function __construct($room_name, $SubVilla, $Villa = null, $Itinerary = null, $options = array())
                $Room = new self::$RoomClass($room_name, $SubVilla, $this, $this->getItinerary()); // Constructor accepts Model Instances.
                $Rooms[] = $Room;
            }

            $this->Rooms = $Rooms; // Set to empty array so we won't run the call to the database again (no longer null) because we know we already queried and found nothing.
            return $this->Rooms;
        }

        return is_array($this->Rooms) ? $this->Rooms : array(); // Will always return array
    }

    /* END ROOMS */

    public static function getAllVillasPostObjects()
    {
        $args = array(
            'post_type' => 'villa',
        );

        $query = new \WP_Query($args);
        $villas = $query->posts;
        return $villas;
    }

    public static function get_youtube_id( $data ) {
        if ( 11 === strlen( $data ) ) {
            return $data;
        }
        preg_match( '/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/', $data, $matches );
        return isset( $matches[2] ) ? $matches[2] : false;
    }


    public static function generate_youtube_embed_url( $youtube_url, $iframe = false ) {
        if ( !$youtube_url ) return '';

        $video_id = get_youtube_id( $youtube_url );

        if ( $video_id && !$iframe ) return 'https://www.youtube.com/embed/' . $video_id;
        elseif ( $video_id && $iframe ) return '<iframe width="560" height="315" src="https://www.youtube.com/embed/' . $video_id . '" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
        else return '';
    }
}
